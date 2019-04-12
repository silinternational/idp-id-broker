<?php
namespace Sil\SilIdBroker\Behat\Context;

use common\helpers\MySqlDateTime;
use common\models\Invite;
use common\models\Method;
use common\models\Mfa;
use common\models\User;
use Webmozart\Assert\Assert;

class UnitTestsContext extends YiiContext
{
    protected const UUID_PATTERN = '/[a-f0-9]{8}\-[a-f0-9]{4}\-4[a-f0-9]{3}\-(8|9|a|b)[a-f0-9]{3}\-[a-f0-9]{12}/';

    /** @var int */
    protected $mfaId = null;

    /** var Mfa */
    protected $mfa;

    /** var bool  */
    protected $mfaIsNew;

    /** @var User */
    protected $tempUser;

    /** @var bool whether the Mfa option is considered newly verified */
    protected $mfaIsNewlyVerified;

    /** @var array The array of changed attributes for an Mfa option */
    protected $mfaChangedAttrs = ['label' => ''];

    /** @var string */
    protected $nagState;

    /** @var Invite */
    protected $oldInviteCode;

    /** @var Invite */
    protected $inviteCode;

    /**
     * Create a new user in the database with the given username (and other
     * details based off that username). If a user already exists with that
     * username, they will be deleted.
     *
     * @param string $username
     * @return User
     */
    protected function createNewUserInDatabase($username)
    {
        $existingUser = User::findByUsername($username);
        if ($existingUser !== null) {
            Assert::notSame($existingUser->delete(), false);
        }

        $user = new User([
            'email' => $username . '@example.com',
            'employee_id' => (string)uniqid(),
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => $username,
        ]);
        $user->scenario = User::SCENARIO_NEW_USER;
        Assert::true(
            $user->save(),
            var_export($user->getErrors(), true)
        );
        Assert::notNull($user);
        return $user;
    }

    protected function createMfa($type, $verified = 1, $user = null)
    {
        $user = $user ?? $this->tempUser;

        $mfa = new Mfa();
        $mfa->user_id = $user->id;
        $mfa->type = $type;
        $mfa->verified = $verified;

        Assert::true($mfa->save(), "Could not create new mfa.");
        $user->refresh();
        $this->mfaId = $mfa['id'];
        $this->mfa = $mfa;
    }

    protected function createMethod($value, $verified = 1, $user = null)
    {
        $user = $user ?? $this->tempUser;

        $method = new Method();
        $method->user_id = $user->id;
        $method->value = $value;
        $method->verified = $verified;

        Assert::true($method->save(), "Could not create new method.");
        $user->refresh();
    }

    /**
     * @When I request a list of verified methods
     */
    public function iRequestAListOfVerifiedMethods()
    {
        $this->methodList = $this->tempUser->getVerifiedMethodOptions();
    }

    /**
     * @Then I see a list containing :num method
     */
    public function iSeeAListContainingMethod($num)
    {
        Assert::count($this->methodList, $num);
    }

    /**
     * @Given the database contains a user with no invite codes
     */
    public function theDatabaseContainsAUserWithNoInviteCodes()
    {
        $this->tempUser = $this->createNewUserInDatabase('method_tester');

        foreach ($this->tempUser->invites as $invite) {
            Assert::notEq(
                false,
                $invite->delete(),
                'Could not purge invites. ' . var_export($invite->getFirstErrors(), true)
            );
        }
    }

    /**
     * @Given /^the database contains a user with a (non-expired|expired) invite code$/
     */
    public function theDatabaseContainsAUserWithANonExpiredInviteCode($expiredOrNot)
    {
        $this->theDatabaseContainsAUserWithNoInviteCodes();

        $invite = new Invite();
        $invite->user_id = $this->tempUser->id;
        $invite->expires_on = MySqlDateTime::relative(
            ($expiredOrNot == 'expired') ? '+0 days' : '+1 day'
        );

        Assert::true($invite->save(), 'Could not create new invite.');
        $this->tempUser->refresh();
    }

    /**
     * @When I request an invite code
     */
    public function iRequestAnInviteCode()
    {
        $this->oldInviteCode = $this->tempUser->invites[0] ?? null;
        $this->inviteCode = Invite::findOrCreate($this->tempUser->id);
    }

    /**
     * @Then I receive a code that is not expired
     */
    public function iReceiveACodeThatIsNotExpired()
    {
        Assert::notNull($this->inviteCode);
        Assert::false($this->inviteCode->isExpired());
    }

    /**
     * @Then the code should be in UUID format
     */
    public function theCodeShouldBeInUuidFormat()
    {
        Assert::notNull($this->inviteCode);
        Assert::regex($this->inviteCode->uuid, self::UUID_PATTERN);
    }


    /**
     * @Then I receive a new code
     */
    public function iReceiveANewCode()
    {
        Assert::notNull($this->inviteCode, 'inviteCode is null');
        Assert::notNull($this->oldInviteCode, 'oldInviteCode is null');
        Assert::notEq($this->inviteCode->uuid, $this->oldInviteCode->uuid);
    }

    /**
     * @Then the new code is not expired
     */
    public function theNewCodeIsNotExpired()
    {
        $this->iReceiveACodeThatIsNotExpired();
    }

    /**
     * @Given the nag dates are in the past
     */
    public function theNagDatesAreInThePast()
    {
        $this->tempUser->review_profile_after = MySqlDateTime::relative('-1 day');
        $this->tempUser->nag_for_method_after = MySqlDateTime::relative('-1 day');
        $this->tempUser->nag_for_mfa_after = MySqlDateTime::relative('-1 day');
    }

    /**
     * @When I request the nag state
     */
    public function iRequestTheNagState()
    {
        $this->nagState = $this->tempUser->getNagState();
    }

    /**
     * @Then I see that the nag state is :state
     */
    public function iSeeThatTheNagStateIs($state)
    {
        Assert::eq($this->nagState, $state);
    }

    /**
     * @Given there is a user in the database
     */
    public function thereIsAUserInTheDatabase()
    {
        $this->tempUser = $this->createNewUserInDatabase('method_tester');
    }

    /**
     * @Given /^that user has (\d+) (verified|unverified) methods?$/i
     */
    public function thatUserHasVerifiedMethods($n, $verifiedOrUnverified)
    {
        for ($i = 0; $i < $n; $i++) {
            $this->createMethod(
                $verifiedOrUnverified . $i . '@example.org',
                $verifiedOrUnverified == 'verified' ? 1 : 0
            );
        }
    }

    /**
     * @Given /^that user has (\d+) (verified|unverified) mfas?/i
     */
    public function thatUserHasVerifiedMfas($n, $verifiedOrUnverified)
    {
        for ($i = 0; $i < $n; $i++) {
            $this->createMfa(
                'totp',
                $verifiedOrUnverified == 'verified' ? 1 : 0
            );
        }
    }
}
