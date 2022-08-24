<?php
namespace Sil\SilIdBroker\Behat\Context;

use common\models\EmailLog;
use common\models\Method;
use common\models\Mfa;
use common\models\MfaBackupcode;
use common\models\User;
use common\models\Password;
use Sil\SilIdBroker\Behat\Context\YiiContext;
use Webmozart\Assert\Assert;

class AnalyticsContext extends YiiContext
{
    /** @var User */
    protected $tempUser;

    /** @var  string */
    protected $mfaCount;

    /** @var  string */
    protected $passwordCount;

    /** @var  string */
    protected $mfaRequiredCount;

    /** @var  float/int */
    protected $mfaAverage;

    /** @var  string */
    protected $mfaOnlyTotpOrU2f;

    /** @var  string */
    protected $noMethodButPersonal;

    protected function createNewUser($makeActive=true, $requireMfa=false)
    {
        $employeeId = uniqid();
        $user = new User([
            'employee_id' => strval($employeeId),
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'test_user_' . $employeeId,
            'email' => 'test_user_' . $employeeId . '@example.com',
            'active' => $makeActive ? 'yes' : 'no',
            'require_mfa' => $requireMfa ? 'yes' : 'no',
        ]);


        $user->scenario = User::SCENARIO_NEW_USER;
        if (! $user->save()) {
            throw new \Exception(
                \json_encode($user->getFirstErrors(), JSON_PRETTY_PRINT)
            );
        }
        $user->refresh();
        return $user;
    }

    protected function createMfa($user, $type, $alreadyVerified=true)
    {
        $mfa = new Mfa();
        $mfa->user_id = $user->id;
        $mfa->type = $type;
        if ($alreadyVerified || $type === Mfa::TYPE_BACKUPCODE) {
            $mfa->verified = 1;
        }
        $mfa->save();
    }

    /**
     * @Given that no mfas or users exist
     */
    public function noMfasOrUsersExist()
    {
        MfaBackupcode::deleteAll();
        Mfa::deleteAll();
        EmailLog::deleteAll();
        User::deleteAll();
    }

    /**
     * @Given I create a(nother) new user
     */
    public function iCreateANewUser()
    {
        $this->tempUser = $this->createNewUser();
    }

    /**
     * @Given I create a new inactive user
     */
    public function iCreateANewInactiveUser()
    {
        $this->tempUser = $this->createNewUser(false);
    }

    /**
     * @Given I create a new user with require mfa
     */
    public function iCreateANewUserWithRequireMfa()
    {
        $this->tempUser = $this->createNewUser(true, true);
    }

    /**
     * @Given I create a new user with a password
     */
    public function iCreateANewUserWithAPassword()
    {
        $newUser = $this->createNewUser();
        $password = uniqid();

        $newUser->scenario = User::SCENARIO_UPDATE_PASSWORD;
        $newUser->password = $password;

        Assert::true($newUser->save());
    }

    /**
     * @Given I create a new user without a password
     */
    public function iCreateANewUserWithoutAPassword()
    {
        $newUser = $this->createNewUser();
        Password::deleteAll(['user_id' => $newUser->id]);
        $newUser->current_password_id = null;

        Assert::true($newUser->save());
    }

    /**
     * @Given that user has a backup code mfa record
     */
    public function thatUserHasABackupCodeMfaRecord()
    {
        $this->createMfa($this->tempUser, Mfa::TYPE_BACKUPCODE);
    }

    /**
     * @Given that user has a verified totp mfa record
     */
    public function thatUserHasAVerifiedTotpMfaRecord()
    {
        $this->createMfa($this->tempUser, Mfa::TYPE_TOTP);
    }

    /**
     * @Given that user has an unverified totp mfa record
     */
    public function thatUserHasAnUnverifiedTotpMfaRecord()
    {
        $this->createMfa($this->tempUser, Mfa::TYPE_TOTP, false);
    }

    /**
     * @Given that user has a(nother) verified webauthn mfa record
     */
    public function thatUserHasAVerifiedWebAuthnMfaRecord()
    {
        $this->createMfa($this->tempUser, Mfa::TYPE_WEBAUTHN);
    }

    /**
     * @Given that user has an unverified webauthn mfa record
     */
    public function thatUserHasAnUnverifiedWebAuthnMfaRecord()
    {
        $this->createMfa($this->tempUser, Mfa::TYPE_WEBAUTHN, false);
    }

    /**
     * @When I get the count of active users with a verified mfa
     */
    public function iGetTheCountOfActiveUsersWithAVerifiedMfa()
    {
        $query = User::getQueryOfUsersWithMfa();
        $this->mfaCount = $query->count();
    }

    /**
     * @When I get the count of active users with a backup code mfa
     */
    public function iGetTheCountOfActiveUsersWithABackupCodeMfa()
    {
        $query = User::getQueryOfUsersWithMfa(Mfa::TYPE_BACKUPCODE);
        $this->mfaCount = $query->count();
    }

    /**
     * @When I get the count of active users with a verified totp mfa
     */
    public function iGetTheCountOfActiveUsersWithAVerifiedTotpMfa()
    {
        $query = User::getQueryOfUsersWithMfa(Mfa::TYPE_TOTP);
        $this->mfaCount = $query->count();
    }

    /**
     * @When I get the count of active users with a verified WebAuthn mfa
     */
    public function iGetTheCountOfActiveUsersWithAVerifiedWebAuthnMfa()
    {
        $query = User::getQueryOfUsersWithMfa(Mfa::TYPE_WEBAUTHN);
        $this->mfaCount = $query->count();
    }

    /**
     * @Then the count of active users with a verified mfa should be :arg1
     */
    public function theCountOfActiveUsersWithAVerifiedMfaShouldBe(int $number)
    {
        Assert::same(
            $this->mfaCount,
            $number
        );
    }

    /**
     * @Then the count of active users with a backup code mfa should be :arg1
     */
    public function theCountOfActiveUsersWithABackupCodeMfaShouldBe(int $number)
    {
        Assert::same(
            $this->mfaCount,
            $number
        );
    }

    /**
     * @Then the count of active users with a verified totp mfa should be :arg1
     */
    public function theCountOfActiveUsersWithAVerifiedTotpMfaShouldBe(int $number)
    {
        Assert::same(
            $this->mfaCount,
            $number
        );
    }

    /**
     * @Then the count of active users with a verified webauthn mfa should be :arg1
     */
    public function theCountOfActiveUsersWithAVerifiedWebAuthnMfaShouldBe(int $number)
    {
        Assert::same(
            $this->mfaCount,
            $number
        );
    }

    /**
     * @When I get the count of active users with a password
     */
    public function iGetTheCountOfActiveUsersWithAPassword()
    {
        $this->passwordCount = User::countUsersWithPassword();
    }

    /**
     * @Then the count of active users with a password should be :arg1
     */
    public function theCountOfActiveUsersWithAPasswordShouldBe(int $number)
    {
        Assert::same(
            $this->passwordCount,
            $number
        );
    }

    /**
     * @When I get the average number of mfas per active user with mfas
     */
    public function iGetTheAverageOfMfasPerActiveUserWithMfas()
    {
        $this->mfaAverage = User::getAverageNumberOfMfasPerUserWithMfas();
    }

    /**
     * @Then the average number of mfas per active user with mfas should be :arg1
     */
    public function theAverageNumberOfMfasPerActiveUserWithMfasShouldBe($number)
    {
        Assert::same(
            (string)$this->mfaAverage,
            $number
        );
    }

    /**
     * @When I get the count of active users with require mfa
     */
    public function iGetTheCountOfActiveUsersWithRequireMfa()
    {
        $this->mfaRequiredCount = User::countUsersWithRequireMfa();
    }

    /**
     * @Then the count of active users with require mfa should be :arg1
     */
    public function theCountOfActiveUsersWithRequireMfaShouldBe(int $number)
    {
        Assert::same(
            $this->mfaRequiredCount,
            $number
        );
    }

    /**
     * @When I get the count of active users with webauthn or totp but not backupcodes
     */
    public function iGetTheCountOfActiveUsersWithWebAuthnOrTotpButNotBackupcodes()
    {
        $this->mfaOnlyTotpOrU2f = User::numberWithOneMfaNotBackupCodes();
    }

    /**
     * @Then the count of active users with webauthn or totp but not backupcodes should be :number
     */
    public function theCountOfActiveUsersWithWebAuthnOrTotpButNotBackupcodesShouldBe(int $number)
    {
        Assert::same(
            $this->mfaOnlyTotpOrU2f,
            $number
        );
    }

    /**
     * @Given that user has a personal email address
     */
    public function thatUserHasAPersonalEmailAddress()
    {
        $this->tempUser->scenario = User::SCENARIO_UPDATE_USER;
        $this->tempUser->personal_email = "email@example.com";
        Assert::true($this->tempUser->save());
    }

    /**
     * @When I get the count of active users with a personal email but no recovery methods
     */
    public function iGetTheCountOfActiveUsersWithAPersonalEmailButNoRecoveryMethods()
    {
        $this->noMethodButPersonal = User::numberWithPersonalEmailButNoMethods();
    }

    /**
     * @Then the count of active users with a personal email but no recovery methods should be :number
     */
    public function theCountOfActiveUsersWithAPersonalEmailButNoRecoveryMethodsShouldBe(int $number)
    {
        Assert::same(
            $this->noMethodButPersonal,
            $number
        );
    }

    protected static function createMethod($user, $alreadyVerified = true)
    {
        $method = new Method();
        $method->user_id = $user->id;
        $method->value = "method@example.com";
        if ($alreadyVerified) {
            $method->verified = 1;
        }
        $method->save();
    }

    /**
     * @Given that user has a recovery method
     */
    public function thatUserHasARecoveryMethod()
    {
        self::createMethod($this->tempUser);
    }

    /**
     * @Given that user has an unverified recovery method
     */
    public function thatUserHasAnUnverifiedRecoveryMethod()
    {
        self::createMethod($this->tempUser, false);
    }
}
