<?php
namespace Sil\SilIdBroker\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use common\models\EmailLog;
use common\models\Mfa;
use common\models\MfaBackupcode;
use common\models\User;
use Webmozart\Assert\Assert;

class MfaContext extends \FeatureContext
{
    /**
     * Mfa $mfa
     */
    protected $mfa;

    /**
     * array $backupCodes
     */
    protected $backupCodes;

    /**
     * @Given the user has a verified :mfaType MFA
     */
    public function iGiveThatUserAVerifiedMfa($mfaType)
    {
        $user = User::findOne(['employee_id' => $this->tempEmployeeId]);
        Assert::notEmpty($user, 'Unable to find that user.');
        $this->mfa = new Mfa([
            'user_id' => $user->id,
            'type' => $mfaType,
            'verified' => 1,
        ]);
        Assert::true($this->mfa->save(), 'Failed to add that MFA record to the database.');
        
        if ($mfaType === 'backupcode') {
            $this->backupCodes = MfaBackupcode::createBackupCodes($this->mfa->id, 10);
        } elseif ($mfaType === 'manager') {
            $this->backupCodes = MfaBackupcode::createBackupCodes($this->mfa->id, 1);
        }
    }

    /**
     * @Then an MFA record exists for an employee_id of :employeeId
     */
    public function anMfaRecordExistsForAnEmployeeIdOf($employeeId)
    {
        $user = User::findOne(['employee_id' => $this->tempEmployeeId]);
        Assert::notEmpty($user, 'Unable to find that user.');

        $this->mfa = Mfa::findOne(['user_id' => $user->id]);
        Assert::notEmpty($this->mfa, 'No MFA record found for that user.');
    }

    /**
     * @Then the following MFA data should be stored:
     */
    public function theFollowingMfaDataShouldBeStored(TableNode $table)
    {
        foreach ($table as $row) {
            $property = $row['property'];
            $expectedValue = $row['value'];

            Assert::eq($this->mfa->$property, $this->transformNULLs($expectedValue));
        }
    }

    /**
     * @Given the user has a manager email address
     */
    public function theUserHasAManagerEmailAddress()
    {
        $dataForTableNode = [
            ['property', 'value'],
            ['manager_email', 'bob_johnson@example.org'],
        ];
        $this->iProvideTheFollowingValidData(new TableNode($dataForTableNode));
        $this->iRequestTheResourceBe('/user/123', 'updated');
        $this->theResponseStatusCodeShouldBe(200);
    }

    /**
     * @Given the user does not have a manager email address
     */
    public function theUserDoesNotHaveAManagerEmailAddress()
    {
        $dataForTableNode = [
            ['property', 'value'],
            ['manager_email', ''],
        ];
        $this->iProvideTheFollowingValidData(new TableNode($dataForTableNode));
        $this->iRequestTheResourceBe('/user/123', 'updated');
        $this->theResponseStatusCodeShouldBe(200);
    }

    /**
     * @When I update the MFA
     */
    public function iUpdateTheMfa()
    {
        $this->iRequestTheResourceBe('/mfa/' . $this->mfa->id, 'updated');
    }

    /**
     * @When I request to verify (one of) the code(s)
     */
    public function iRequestToVerifyOneOfTheCodes()
    {
        $dataForTableNode = [
            ['property', 'value'],
            ['employee_id', '123'],
            ['value', $this->backupCodes[0]],
        ];

        $this->iProvideTheFollowingValidData(new TableNode($dataForTableNode));
        $this->iRequestTheResourceBe('/mfa/' . $this->mfa->id . '/verify', 'created');
    }

    /**
     * @Then :num codes should be stored
     */
    public function codesShouldBeStored($num)
    {
        Assert::eq($num, count($this->mfa->mfaBackupcodes));
    }

    /**
     * @When I request to delete the MFA
     */
    public function iRequestToDeleteTheMfa()
    {
        $dataForTableNode = [
            ['property', 'value'],
            ['employee_id', '123'],
        ];

        $this->iProvideTheFollowingValidData(new TableNode($dataForTableNode));
        $this->iRequestTheResourceBe('/mfa/' . $this->mfa->id, 'deleted');
    }

    /**
     * @Then the MFA record is not stored
     */
    public function theMfaRecordIsNotStored()
    {
        $this->mfa = Mfa::findOne(['id' => $this->mfa->id]);
        Assert::null($this->mfa, 'A matching record was found in the database');
    }
}
