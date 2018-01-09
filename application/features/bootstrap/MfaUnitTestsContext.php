<?php
namespace Sil\SilIdBroker\Behat\Context;

use common\models\Mfa;
use common\models\MfaBackupcode;
use common\models\MfaFailedAttemptBase;
use common\models\User;
use Sil\SilIdBroker\Behat\Context\fakes\FakeOfflineLdap;
use Webmozart\Assert\Assert;
use Yii;

class MfaUnitTestsContext extends YiiContext
{
    /** @var int */
    protected $mfaId = null;

    /** @var MfaBackupcode that was input by the user for validation */
    protected $inputBackupCode;

    /** @var bool  */
    protected $backupCodeWasValid;


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

    /**
     * @Given I have a user with a backup codes mfa option
     */
    public function iHaveAUserWithABackupCodesMfaOption()
    {
        $user = $this->createNewUserInDatabase('mfa_tester');
        $mfaCreateResult = Mfa::create($user->id, Mfa::TYPE_BACKUPCODE);

        $this->mfaId = $mfaCreateResult['id'];
        Assert::notEmpty($this->mfaId);
        MfaBackupcode::deleteAll();
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
        $this->backupCodeWasValid = MfaBackupcode::validateAndRemove($this->mfaId, $this->inputBackupCode);
    }

    /**
     * @When a not matching backup code is provided for validation
     */
    public function aNOTMatchingBackupCodeIsProvidedForValidation()
    {
        $this->backupCodeWasValid = MfaBackupcode::validateAndRemove($this->mfaId, '76543210');
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
}
