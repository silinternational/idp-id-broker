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
     * array $mfaWebauthn
     *
     */
    protected $mfaWebauthn;

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
     * @Given the user has a verified mfaWebauthn with a key_handle_hash of :keyHandleHash
     */
    public function iGiveThatUserAVerifiedMfaWebauthnMfaWithAKeyHandleHashOf($keyHandleHash)
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
        $this->mfaWebauthn = $webauthn;
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
     * @Then the mfaWebauthn record exists
     */
    public function theMfaWebauthnRecordExists()
    {
        $this->mfaWebauthn = MfaWebauthn::findOne(['id' => $this->mfaWebauthn->id]);
        Assert::notEmpty($this->mfaWebauthn, 'No MfaWebauthn record found with that id.');
    }

    /**
     * @Then the following mfaWebauthn data should be stored:
     */
    public function theFollowingMfaWebauthnDataShouldBeStored(TableNode $table)
    {
        foreach ($table as $row) {
            $property = $row['property'];
            $expectedValue = $row['value'];

            Assert::eq($this->mfaWebauthn->$property, $this->transformNULLs($expectedValue));
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

        // It is too complicated at this point to come up with completely correct values
        // These should get as far as producing a 400 status code with
        // "error":"unable to create credential: Error validating challenge"

        // These values are from values produced in serverless-mfa-api-go/webauthn_test.go Test_FinishRegistration
        $reqValue = [
            'id' => 'dmlydEtleTExLTA',
            'rawId' => 'dmlydEtleTExLTA',
            'type' => 'public-key',
            'response' => [
                'authenticatorData' => 'hgW4ugjCDUL55FUVGHGJbQ4N6YBZYob7c20R7sAT4qRBAAAAAAAAAAAAAAAAAAAAAAAAAAAADHZpcnR1YWxrZXkxMaQBAgMmIVggBtYaQhitMvmuvKeeUZmuh96TmXTRGxB_6bfslWmTVF4iWCCK1h-O_T8R6MjkIWCsX-Pry8RJhuOxbDwovnYJBu0SZw',
                'clientDataJSON' => 'eyJ0eXBlIjoid2ViYXV0aG4uY3JlYXRlIiwiY2hhbGxlbmdlIjoidXdGS2ZyRTk3Qm1yWWFmUjhUZW5kUjJKbWxkekVlQ3paRTFnL0FhYm03bz0iLCJvcmlnaW4iOiJodHRwOi8vbG9jYWxob3N0IiwiY2lkX3B1YmtleSI6bnVsbH0=',
                'attestationObject' => 'pGNmbXRoZmlkby11MmZnYXR0U3RtdKJjc2lnWEgwRgIhAL2a2xVBg_4Ooc-m27dxItzeUsROR7PLh2wHa0ZTerhYAiEA9trBQ8Yr6MPPdeNaN4BE8fuR4aV2iL8UL95JfB4F-khjeDVjgVkBJzCCASMwgcmgAwIBAgIhARDyEPt8s80lRZ3lTdjXIo0Dp3dfBJd1nOqwKNDeOPNOMAoGCCqGSM49BAMCMAAwIBcNMjIwMTAxMDEwMTAxWhgPMjEyMjAxMDEwMTAxMDFaMAAwWTATBgcqhkjOPQIBBggqhkjOPQMBBwNCAAQG1hpCGK0y-a68p55Rma6H3pOZdNEbEH_pt-yVaZNUXorWH479PxHoyOQhYKxf4-vLxEmG47FsPCi-dgkG7RJnoxIwEDAOBgNVHQ8BAf8EBAMCAqQwCgYIKoZIzj0EAwIDSQAwRgIhAIXIqNEsaurdLaUiLG5_srVUw8fZZyJ268Hh8iFp3Xb2AiEA-v_2ik8SC8_EhQzN4RkgHRseGr-y0DymcIbdrpODjYpoQXV0aERhdGGlZHJwaWT2ZWZsYWdzAGhhdHRfZGF0YaNmYWFndWlk9mpwdWJsaWNfa2V59m1jcmVkZW50aWFsX2lk9mhleHRfZGF0YfZqc2lnbl9jb3VudABoYXV0aERhdGFYjoYFuLoIwg1C-eRVFRhxiW0ODemAWWKG-3NtEe7AE-KkQQAAAAAAAAAAAAAAAAAAAAAAAAAAAAx2aXJ0dWFsa2V5MTGkAQIDJiFYIAbWGkIYrTL5rrynnlGZrofek5l00RsQf-m37JVpk1ReIlggitYfjv0_EejI5CFgrF_j68vESYbjsWw8KL52CQbtEmc',
            ],
            'user' => [
                'displayName' => $user->display_name,
                'id' => $user->id,
                'name' => $user->username,
            ],
            'transports' => ['usb'],
        ];

        $reqJson = json_encode($reqValue);
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
     * @When I update the mfaWebauthn
     */
    public function iUpdateTheMfaWebauthn()
    {
        $this->iRequestTheResourceBe('/mfa/' . $this->mfa->id . '/webauthn/' . $this->mfaWebauthn->id, 'updated');
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
     * @When I request to verify the webauthn Mfa registration
     */
    public function iRequestToVerifyTheWebauthnMfaRegistration()
    {
        $this->iRequestTheResourceBe('/mfa/' . $this->mfa->id . '/verify/registration', 'created');
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
