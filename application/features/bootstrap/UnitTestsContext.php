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
     * @Given A user with :verMeth verified method(s), :unverMeth unverified method(s), :verMfa verified mfa(s), and :unverMfa unverified mfa(s)
     */
    public function aUserWithVerifiedMethodsVerifiedMfas($verMeth, $unverMeth, $verMfa, $unverMfa)
    {
        $this->tempUser = $this->createNewUserInDatabase('method_tester');

        for ($i = 0; $i < $verMeth; $i++) {
            $this->createMethod('verified' . $i . '@example.org', 1);
        }

        for ($i = 0; $i < $unverMeth; $i++) {
            $this->createMethod('unverified' . $i . '@example.org', 0);
        }

        for ($i = 0; $i < $verMfa; $i++) {
            $this->createMfa('totp', 1);
        }

        for ($i = 0; $i < $unverMfa; $i++) {
            $this->createMfa('totp', 0);
        }
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
     * @Given the nag dates are in the past
     */
    public function theNagDatesAreInThePast()
    {
        $this->tempUser->nag_for_method_after = MySqlDateTime::formatDate(time() - (60 * 60 * 24));
        $this->tempUser->nag_for_mfa_after = MySqlDateTime::formatDate(time() - (60 * 60 * 24));
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
     * @Then I update the nag dates
     */
    public function iUpdateTheNagDates()
    {
        $this->tempUser->updateNagDates();
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
        $this->inviteCode = Invite::getInviteCode($this->tempUser->id);
    }

    /**
     * @Then I receive a code that is not expired
     */
    public function iReceiveACodeThatIsNotExpired()
    {
        Assert::notNull($this->inviteCode);
        Assert::true($this->inviteCode->isValidCode());
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
}
