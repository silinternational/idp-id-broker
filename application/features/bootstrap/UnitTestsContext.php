<?php

namespace Sil\SilIdBroker\Behat\Context;

use common\helpers\MySqlDateTime;
use common\models\EmailLog;
use common\models\Invite;
use common\models\Method;
use common\models\Mfa;
use common\models\MfaBackupcode;
use common\models\MfaWebauthn;
use common\models\Password;
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

    /** @var string[] */
    protected $originalParams = [];

    protected $dataToVerify;

    private string $password;

    /**
     * @afterScenario @database
     */
    public function purgeDatabase()
    {
        MfaBackupcode::deleteAll();
        Mfa::deleteAll();
        Invite::deleteAll();
        EmailLog::deleteAll();
        Method::deleteAll();
        User::deleteAll();
    }

    /**
     * Create a new user in the database with the given username (and other
     * details based off that username). If a user already exists with that
     * username, they will be deleted.
     *
     * @param string $username
     * @param string[] $properties
     * @return User
     */
    protected function createNewUserInDatabase($username, $properties = [])
    {
        Assert::false(
            array_key_exists('username', $properties),
            'properties array cannot override username'
        );

        $existingUser = User::findByUsername($username);
        if ($existingUser !== null) {
            Assert::notSame($existingUser->delete(), false);
        }

        $mergedProperties = array_merge([
            'email' => $username . '@example.com',
            'employee_id' => (string)uniqid(),
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => $username,
        ], $properties);

        $user = new User($mergedProperties);
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

    protected function createMfaBackupCodes($user = null)
    {
        $user = $user ?? $this->tempUser;

        $mfa = new Mfa();
        $mfa->user_id = $user->id;
        $mfa->type = MFA::TYPE_BACKUPCODE;
        $mfa->verified = 1;

        Assert::true($mfa->save(), "Could not create new mfa.");

        MfaBackupcode::createBackupCodes($mfa->id, 3);

        $user->refresh();
        $this->mfaId = $mfa['id'];
        $this->mfa = $mfa;
    }

    protected function createMfaWebauthn($user = null)
    {
        $user = $user ?? $this->tempUser;

        $mfa = new Mfa();
        $mfa->user_id = $user->id;
        $mfa->type = MFA::TYPE_WEBAUTHN;
        $mfa->verified = 1;
        $mfa->external_uuid = 'abc123'; // Just to avoid an exception on delete

        Assert::true($mfa->save(), "Could not create new mfa.");
        MfaWebauthn::createWebauthn($mfa, 'abc123');

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
        $this->tempUser = $this->createNewUserInDatabase('unit_test');

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
        $this->tempUser = $this->createNewUserInDatabase('unit_test');
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

    /**
     * @Given that user has a verified backup code mfa
     */
    public function thatUserHasAVerifiedBackupCodeMfa()
    {
        $this->createMfaBackupCodes();
    }

    /**
     * @Given that user has a verified webauthn mfa
     */
    public function thatUserHasAVerifiedWebauthnMfa()
    {
        $this->createMfaWebauthn();
    }

    /**
     * @When I create a new user with a :property property of :value
     */
    public function iCreateANewUserWithAPropertyOf($property, $value)
    {
        $this->tempUser = $this->createNewUserInDatabase('test_user', [$property => $value]);
    }

    /**
     * @Given the database contains a user (with no MFA options)
     */
    public function theDatabaseContainsAUser()
    {
        $this->tempUser = $this->createNewUserInDatabase('test_user');
    }

    /**
     * @Given that user has a :property property value of :value
     */
    public function thatUserHasAPropertyValueOf($property, $value)
    {
        $this->iChangeTheUsersPropertyTo($property, $value);
    }

    /**
     * @When I change the user's :property property to :value
     */
    public function iChangeTheUsersPropertyTo($property, $value)
    {
        $this->tempUser->scenario = User::SCENARIO_UPDATE_USER;
        $this->tempUser->$property = $value;
        Assert::eq(true, $this->tempUser->save());
    }

    /**
     * @Then I see the user's :property property is :value
     */
    public function iSeeTheUsersPropertyIs($property, $value)
    {
        $this->tempUser->refresh();
        Assert::eq($this->tempUser->$property, $value);
    }

    /**
     * @Given the :param config parameter is true
     */
    public function theConfigParameterIsTrue($param)
    {
        $this->originalParams[$param] = \Yii::$app->params[$param];
        \Yii::$app->params[$param] = true;
    }

    /**
     * @Given the :param config parameter is false
     * Behat can't seem to pass a false boolean correctly as an argument
     */
    public function theConfigParameterIsFalse($param)
    {
        $this->originalParams[$param] = \Yii::$app->params[$param];
        \Yii::$app->params[$param] = false;
    }


    /**
     * @AfterScenario
     */
    public function resetParams()
    {
        foreach ($this->originalParams as $param => $value) {
            \Yii::$app->params[$param] = $value;
        }
    }

    /**
     * @When I add backup codes for that user
     */
    public function iAddBackupCodesForThatUser()
    {
        $this->createMfa(Mfa::TYPE_BACKUPCODE);
    }

    /**
     * @Given the user has not logged in for :months
     */
    public function theUserHasNotLoggedInFor($months)
    {
        $date = MySqlDateTime::relative("-{$months}");
        $this->iChangeTheUsersPropertyTo("last_login_utc", $date);
        $this->iChangeTheUsersPropertyTo("created_utc", $date);
    }

    /**
     * @When I get users for HR notification
     */
    public function iGetUsersForHrNotification()
    {
        $this->dataToVerify = User::getAbandonedUsers();
    }

    /**
     * @Then /^the user (is|is NOT) included in the data$/
     */
    public function isUserIncludedInTheData($option)
    {
        $isIncluded = false;
        foreach ($this->dataToVerify as $user) {
            if ($user->uuid === $this->tempUser->uuid) {
                $isIncluded = true;
                break;
            }
        }

        if ($option === "is") {
            Assert::true($isIncluded);
        } else {
            Assert::false($isIncluded);
        }
    }

    /**
     * @When I delete inactive users
     */
    public function iDeleteInactiveUsers()
    {
        // Temporarily set the configs to something that will allow
        // newly created users to be considered for deletion
        $originalPeriod = \Yii::$app->params['inactiveUserPeriod'];
        \Yii::$app->params['inactiveUserPeriod'] = '-2 days';
        \Yii::$app->params['inactiveUserDeletionEnable'] = true;
        User::deleteInactiveUsers();

        // Put configs back as they were
        \Yii::$app->params['inactiveUserDeletionEnable'] = false;
        \Yii::$app->params['inactiveUserPeriod'] = $originalPeriod;
    }

    /**
     * @When I retrieve the remaining users
     */
    public function IRetrieveTheRemainingUsers()
    {
        $this->dataToVerify = User::find()->all();
    }

    /**
     * @When the user submits a new password
     */
    public function theUserSubmitsANewPassword()
    {
        $this->tempUser->password = 'k23@U$%235u25@I2$o';
        $this->tempUser->setScenario(User::SCENARIO_UPDATE_PASSWORD);
        $this->tempUser->save();
    }

    /**
     * @Then a new password hash should be stored
     */
    public function aNewPasswordHashShouldBeStored()
    {
        $hash = $this->tempUser->currentPassword->hash;
        Assert::notEmpty($hash);
    }

    /**
     * @Given that user has a password with a low hash cost
     */
    public function thatUserHasAPasswordWithALowHashCost()
    {
        $this->password = 'k23@U24urchr,@I2$o';
        $passwordObject = new Password();
        $passwordObject->hash = password_hash(
            $this->password,
            Password::HASH_ALGORITHM,
            ["cost" => Password::HASH_COST - 1],
        );
        $passwordObject->user_id = $this->tempUser->id;
        Assert::true($passwordObject->save(false), var_export($passwordObject->errors, true));

        $this->tempUser->current_password_id = $passwordObject->id;
        Assert::true($this->tempUser->save(), var_export($passwordObject->errors, true));
    }

    /**
     * @When the user uses their password
     */
    public function theUserUsesTheirPassword()
    {
        $this->tempUser->scenario = User::SCENARIO_AUTHENTICATE;
        $this->tempUser->password = $this->password;
        Assert::true($this->tempUser->validate(), var_export($this->tempUser->errors, true));
    }

    /**
     * @Then the password hash should be updated
     */
    public function thePasswordHashShouldBeUpdated()
    {
        $currentPassword = $this->tempUser->getCurrentPassword()->one();
        $hash = $currentPassword->hash;
        $bad = password_needs_rehash($hash, Password::HASH_ALGORITHM, ["cost" => Password::HASH_COST]);
        Assert::false($bad);
    }
}
