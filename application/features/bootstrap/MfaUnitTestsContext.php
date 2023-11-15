<?php

namespace Sil\SilIdBroker\Behat\Context;

use common\models\Mfa;
use common\models\MfaBackupcode;
use Webmozart\Assert\Assert;

class MfaUnitTestsContext extends UnitTestsContext
{
    protected $label;

    protected $inputBackupCode;

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
     * @Given I have a user with a manager rescue mfa option
     */
    public function iHaveAUserWithAManagerRescueMfaOption()
    {
        $this->tempUser = $this->createNewUserInDatabase('mfa_tester');
        $this->createMfa(Mfa::TYPE_MANAGER);
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
     * @When I check if the new mfa option is newly verified
     */
    public function iCheckIfTheNewMfaOptionIsNewlyVerified()
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

    /**
     * @Given that user also has a manager rescue mfa option
     */
    public function thatUserAlsoHasAManagerRescueMfaOption()
    {
        $code = 'manager';
        $this->createMfa(Mfa::TYPE_MANAGER);
        MfaBackupcode::insertBackupCode($this->mfaId, $code);
    }

    /**
     * @When I verify a backup code
     */
    public function iVerifyABackupCode()
    {
        $code = 'backup';
        $mfa = Mfa::findOne(['user_id' => $this->tempUser->id, 'type' => Mfa::TYPE_BACKUPCODE]);
        MfaBackupcode::insertBackupCode($mfa->id, $code);
        Assert::true($mfa->verify($code));
    }

    /**
     * @Then I see that the user no longer has a manager rescue mfa option
     */
    public function iSeeThatTheUserNoLongerHasAManagerRescueMfaOption()
    {
        $mfa = Mfa::findOne(['user_id' => $this->tempUser->id, 'type' => Mfa::TYPE_MANAGER]);
        Assert::null($mfa);
    }
}
