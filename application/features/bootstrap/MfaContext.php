<?php
namespace Sil\SilIdBroker\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use common\models\EmailLog;
use common\models\Mfa;
use common\models\MfaBackupcode;
use common\models\MfaWebauthn;
use common\models\User;
use Webmozart\Assert\Assert;

class MfaContext extends \FeatureContext
{
    /**
     * Mfa $mfa
     */
    protected $mfa;

    /**
     * array $mfaWebauthnIds
     *
     */
    protected $mfaWebauthnIds;

    /**
     * array $backupCodes
     */
    protected $backupCodes;

    /**
     * @Given the user has a verified :mfaType MFA
     */
    public function iGiveThatUserAVerifiedMfa($mfaType)
    {
        Assert::notEq($mfaType, mfa::TYPE_WEBAUTHN, "should have called iGiveThatUserAVerifiedWebauthnMfa");

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
     * @Given the user has a verified webauthn MFA with a key_handle_hash of :keyHandleHash
     */
    public function iGiveThatUserAVerifiedWebauthnMfaWithAKeyHandleHashOf($keyHandleHash)
    {
        $user = User::findOne(['employee_id' => $this->tempEmployeeId]);
        Assert::notEmpty($user, 'Unable to find that user.');

        if (empty($this->mfa)) {
            $this->mfa = new Mfa([
                'user_id' => $user->id,
                'type' => mfa::TYPE_WEBAUTHN,
                'verified' => 1,
                'external_uuid' => '097791bf-2385-4ab4-8b06-14561a338d8e',
            ]);
            Assert::true($this->mfa->save(), 'Failed to add that MFA record to the database.');
        }

        $webauthn = MfaWebauthn::createWebauthn($this->mfa, $keyHandleHash);
        $this->mfaWebauthnIds[] = $webauthn->id;
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
     * @Given the user has requested a new webauthn MFA
     */
    public function theUserHasRequestedANewWebauthnMfa()
    {
        $user = User::findOne(['employee_id' => $this->tempEmployeeId]);
        Assert::notEmpty($user, 'Unable to find that user.');
        $this->setRequestBody('type', Mfa::TYPE_WEBAUTHN);
        $this->iRequestTheResourceBe('/mfa', 'created');

        $id = $this->getResponseProperty('id');
        Assert::notEmpty($id, 'Unable to get id of new Webauthn MFA');
        $mfa = Mfa::FindOne(['id'=>$id]);
        Assert::notEmpty($mfa, 'Unable to find that MFA.');


        $resData = $this->getResponseProperty('data');
        Assert::notEmpty($resData, "unable to find 'data' entry in the response");

        $publicKey = $resData['publicKey'];
        Assert::notEmpty($publicKey, "unable to find 'publicKey' entry in the reponse");

        // It is too complicated at this point to come up with completely correct values
        // These should get as far as producing a 400 status code with
        // "error":"unable to create credential: Error validating challenge"

        // These values are from the constants and tests in serverless-mfa-api-go/webauthn_test.go

        $reqValue = [
            'id' => 'dmlydEtleTExLTA',
            'rawId' => 'dmlydEtleTExLTA',
            'type' => 'public-key',
            'response' => [
                'authenticatorData' => 'dKbqkhPJnC90siSSsyDPQCYqlMGpUKA5fyklC2CEHvBFXJJiGa3OAAI1vMYKZIsLJfHwVQMANwCOw-atj9C0vhWpfWU-whzNjeQS21Lpxfdk_G-omAtffWztpGoErlNOfuXWRqm9Uj9ANJck1p6lAQIDJiABIVggKAhfsdHcBIc0KPgAcRyAIK_-Vi-nCXHkRHPNaCMBZ-4iWCBxB8fGYQSBONi9uvq0gv95dGWlhJrBwCsj_a4LJQKVHQ',
                'clientDataJSON' => 'eyJjaGFsbGVuZ2UiOiJXOEd6RlU4cEdqaG9SYldyTERsYW1BZnFfeTRTMUNaRzFWdW9lUkxBUnJFIiwib3JpZ2luIjoiaHR0cHM6Ly93ZWJhdXRobi5pbyIsInR5cGUiOiJ3ZWJhdXRobi5jcmVhdGUifQ',
                'attestationObject' => 'o2NmbXRkbm9uZWdhdHRTdG10oGhhdXRoRGF0YVjEdKbqkhPJnC90siSSsyDPQCYqlMGpUKA5fyklC2CEHvBBAAAAAAAAAAAAAAAAAAAAAAAAAAAAQOsa7QYSUFukFOLTmgeK6x2ktirNMgwy_6vIwwtegxI2flS1X-JAkZL5dsadg-9bEz2J7PnsbB0B08txvsyUSvKlAQIDJiABIVggLKF5xS0_BntttUIrm2Z2tgZ4uQDwllbdIfrrBMABCNciWCDHwin8Zdkr56iSIh0MrB5qZiEzYLQpEOREhMUkY6q4Vw',
            ],
            'user' => [
                'displayName' => $user->display_name,
                'id' => $user->id,
                'name' => $user->username,
            ],
            'transports' => ['usb'],
        ];

        $reqJson = json_encode($reqValue);

//        print_r(PHP_EOL . "GGGGGGGGG  " . $reqJson . PHP_EOL);

        $this->setRequestBody('value', $reqJson);

        $this->mfa = $mfa;
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
     * @When I request to verify the webauthn Mfa
     */
    public function iRequestToVerifyTheWebauthnMfa()
    {
        $this->iRequestTheResourceBe('/mfa/' . $this->mfa->id . '/verify', 'created');
    }

    /**
     * @Then :num codes should be stored
     */
    public function codesShouldBeStored($num)
    {
        Assert::eq(count($this->mfa->mfaBackupcodes), $num);
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
     * @When I request to delete the webauthn entry of the MFA with a webauthn_id of :webauthnId
     */
    public function iRequestToDeleteTheWebauthnEntryOfTheMfaWithAWebauthnIDOf($webauthnId)
    {
        $dataForTableNode = [
            ['property', 'value'],
            ['employee_id', '123'],
        ];

        $this->iProvideTheFollowingValidData(new TableNode($dataForTableNode));
        $this->iRequestTheResourceBe('/mfa/' . $this->mfa->id . '/webauthn/' . $webauthnId,
            'deleted');
    }


    /**
     * @When I request to delete the webauthn entry of the MFA
     */
    public function iRequestToDeleteTheWebauthnEntryOfTheMfa()
    {
        $webauthnId = $this->mfaWebauthnIds[0];
        $this->iRequestToDeleteTheWebauthnEntryOfTheMfaWithAWebauthnIDOf($webauthnId);
    }


    /**
     * @When the user requests a new webauthn MFA
     */
    public function theUserRequestsANewWebauthnMfa()
    {
        $user = User::findOne(['employee_id' => $this->tempEmployeeId]);
        Assert::notEmpty($user, 'Unable to find that user.');
        $this->setRequestBody('type', Mfa::TYPE_WEBAUTHN);
        $this->iRequestTheResourceBe('/mfa', 'created');
        print_r(PHP_EOL . "RRRRRR  " . var_export($this->getResponseBody(), true) . PHP_EOL);
    }


    /**
     * @Then the MFA record is not stored
     */
    public function theMfaRecordIsNotStored()
    {
        $this->mfa = Mfa::findOne(['id' => $this->mfa->id]);
        Assert::null($this->mfa, 'A matching record was found in the database');
    }

    /**
     * @Then the MFA record is still stored
     */
    public function theMfaRecordIsStillStored()
    {
        $this->mfa = Mfa::findOne(['id' => $this->mfa->id]);
        Assert::notNull($this->mfa, 'A matching record was not found in the database');
    }
}
