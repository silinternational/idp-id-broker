<?php
namespace Sil\SilIdBroker\Behat\Context;

use common\models\Mfa;
use common\models\MfaBackupcode;
use common\models\User;
use Webmozart\Assert\Assert;

class MfaUnitTestsContext extends YiiContext
{
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

    protected function createMfa($type, $verified=1, $user=null)
    {
        if ($user ===null) {
            $user = $this->tempUser;
        }
        $mfa = new Mfa();
        $mfa->user_id = $user->id;
        $mfa->type = $type;
        $mfa->verified = $verified;

        Assert::true($mfa->save(), "Could not create new mfa.");
        $user->refresh();
        $this->mfaId = $mfa['id'];
        $this->mfa = $mfa; 
    }

    /**
     * @Given I have a user with a backup codes mfa option
     */
    public function iHaveAUserWithABackupCodesMfaOption()
    {
        $this->tempUser = $this->createNewUserInDatabase('mfa_tester');
        $this->createMfa(Mfa::TYPE_BACKUPCODE);
        MfaBackupcode::deleteAll();
    }

    /**
     * @Given I have a user with an unverified totp mfa option
     */
    public function iHaveAUserWithAnUnverifiedTotpMfaOption()
    {
        Mfa::deleteAll();
        $this->tempUser = $this->createNewUserInDatabase('mfa_tester');
        $this->createMfa(Mfa::TYPE_TOTP, 0);
        Assert::isEmpty(
            $this->mfa->verified,
            'Totp option should not have been verified already'
        );
    }

    /**
     * @Given I have a user with a verified totp mfa option
     */
    public function iHaveAUserWithAVerifiedTotpMfaOption()
    {
        Mfa::deleteAll([]);
        $this->tempUser = $this->createNewUserInDatabase('mfa_tester');
        $this->createMfa(Mfa::TYPE_TOTP, 1);
        Assert::notEmpty(
            $this->mfa->verified,
            'Totp option should have been verified already'
        );
    }

    /**
     * @Given the totp mfa option is new
     */
    public function theTotpMfaOptionIsNew()
    {
        $this->mfaIsNew = true;
    }

    /**
     * @Given the totp mfa option is old
     */
    public function theTotpMfaOptionIsOld()
    {
        $this->mfaIsNew = false;
    }

    /**
     * @Given the totp mfa option has just been verified
     */
    public function theTotpMfaOptionHasJustBeenVerified()
    {
        $this->mfaChangedAttrs['verified'] = 0;
    }

    /**
     * @Given a backup code with a leading zero was saved and was shortened
     */
    public function aBackupCodeWithALeadingZeroWasSavedAndWasShortened()
    {
        $fullBackupCode = '01234567';
        $this->inputBackupCode = $fullBackupCode;

        MfaBackupcode::insertBackupCode($this->mfaId, substr($fullBackupCode, 1));
    }

    /**
     * @Given a backup code with a leading zero was saved and was not shortened
     */
    public function aBackupCodeWithALeadingZeroWasSavedAndWasNotShortened()
    {
        $fullBackupCode = '01234567';
        $this->inputBackupCode = $fullBackupCode;

        MfaBackupcode::insertBackupCode($this->mfaId, $fullBackupCode);
    }

    /**
     * @Given a backup code without a leading zero was saved and was not shortened
     */
    public function aBackupCodeWithoutALeadingZeroWasSavedAndWasNotShortened()
    {
        $fullBackupCode = '81234567';
        $this->inputBackupCode = $fullBackupCode;

        MfaBackupcode::insertBackupCode($this->mfaId, $fullBackupCode);
    }

    /**
     * @When a matching backup code is provided for validation
     */
    public function aMatchingBackupCodeIsProvidedForValidation()
    {
        $this->backupCodeWasValid = MfaBackupcode::validateAndRemove(
            $this->mfaId,
            $this->inputBackupCode
        );
    }

    /**
     * @When a not matching backup code is provided for validation
     */
    public function aNOTMatchingBackupCodeIsProvidedForValidation()
    {
        $this->backupCodeWasValid = MfaBackupcode::validateAndRemove(
            $this->mfaId,
            '76543210'
        );
    }

    /**
     * @When I check if the new backup codes mfa option is newly verified
     */
    public function iCheckIfTheNewBackupCodesMfaOptionIsNewlyVerified()
    {
        $this->mfaIsNewlyVerified = $this->mfa->isNewlyVerified(true, []);
    }

    /**
     * @When I check if the mfa option is newly verified
     */
    public function iCheckIfTheMfaOptionIsNewlyVerified()
    {
        $this->mfaIsNewlyVerified = $this->mfa->isNewlyVerified(
            $this->mfaIsNew,
            $this->mfaChangedAttrs
        );
    }

    /**
     * @Then a backup code match should be detected
     */
    public function aBackupCodeMatchShouldBeDetected()
    {
        Assert::true($this->backupCodeWasValid);
    }

    /**
     * @Then a backup code match should not be detected
     */
    public function aBackupCodeMatchShouldNotBeDetected()
    {
        Assert::false($this->backupCodeWasValid);
    }

    /**
     * @Then :number backup codes should exist
     */
    public function xBackupCodesShouldExist($number)
    {
        Assert::eq($number, MfaBackupcode::find()->count());
    }

    /**
     * @Then I see that the mfa option is newly verified
     */
    public function iSeeThatTheMfaOptionIsNewlyVerified()
    {
        Assert::true($this->mfaIsNewlyVerified);
    }

    /**
     * @Then I see that the mfa option is NOT newly verified
     */
    public function iSeeThatTheMfaOptionIsNotNewlyVerified()
    {
        Assert::false($this->mfaIsNewlyVerified);
    }
}
