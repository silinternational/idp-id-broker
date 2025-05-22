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

        switch ($mfaType) {
            case Mfa::TYPE_BACKUPCODE:
                $this->backupCodes = MfaBackupcode::createBackupCodes($this->mfa->id, 10);
                break;
            case Mfa::TYPE_MANAGER:
            case Mfa::TYPE_RECOVERY:
                $this->backupCodes = MfaBackupcode::createBackupCodes($this->mfa->id, 1);
                break;
            default:
                break;
        }
    }

    /**
     * @Given the user has a mfaWebauthn with a key_handle_hash of :keyHandleHash
     */
    public function iGiveThatUserAMfaWebauthnMfaWithAKeyHandleHashOf($keyHandleHash)
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
     * @Then the following mfaWebauthn data should be stored:
     */
    public function theFollowingMfaWebauthnDataShouldBeStored(TableNode $table)
    {
        $this->mfaWebauthn = MfaWebauthn::findOne(['id' => $this->mfaWebauthn->id]);
        Assert::notEmpty($this->mfaWebauthn, 'No MfaWebauthn record found with that id.');

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
        $this->iRequestTheResourceBe('/user/123', self::UPDATED);
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
        $this->iRequestTheResourceBe('/user/123', self::UPDATED);
        $this->theResponseStatusCodeShouldBe(200);
    }

    /**
     * @Given the user has requested a new webauthn MFA
     */
    public function theUserHasRequestedANewWebauthnMfa()
    {
        $rpId = getenv('MFA_WEBAUTHN_rpId');
        $user = User::findOne(['employee_id' => $this->tempEmployeeId]);
        Assert::notEmpty($user, 'Unable to find that user.');
        $this->setRequestBody('type', Mfa::TYPE_WEBAUTHN);
        $this->iRequestTheResourceBe('/mfa', self::CREATED);

        $id = $this->getResponseProperty('id');
        Assert::notEmpty($id, 'Unable to get id of new Webauthn MFA');
        $mfa = Mfa::FindOne(['id' => $id]);
        Assert::notEmpty($mfa, 'Unable to find that MFA.');

        // Ensure we're getting a challenge in the response
        $responseData = $this->getResponseProperty('data');
        Assert::notEmpty($responseData['publicKey'], 'response data is missing publicKey entry');
        $publicKey = $responseData['publicKey'];
        Assert::notEmpty($publicKey['challenge'], 'publicKey entry is missing challenge entry');

        $this->cleanRequestBody();
        $this->setRequestBody('challenge', $publicKey['challenge']);
        $this->setRequestBody('relying_party_id', $rpId);

        // now call the u2f-simulator to get the values to send to the finish registration endpoint
        $this->callU2fSimulator('/u2f/registration', self::CREATED, $user, $mfa->external_uuid);

        $respBody = $this->getResponseBody();
        Assert::notEmpty($respBody['id'], "registration response is missing an id entry");
        Assert::notEmpty($respBody['rawId'], "registration response is missing an rawId entry");
        Assert::notEmpty($respBody['response'], "registration response is missing a response entry");
        Assert::notEmpty($respBody['response']['authenticatorData'], "registration response is missing a authenticatorData entry");
        Assert::notEmpty($respBody['response']['attestationObject'], "registration response is missing a attestationObject entry");
        Assert::notEmpty($respBody['response']['clientDataJSON'], "registration response is missing a clientDataJSON entry");
        Assert::notEmpty($respBody['type'], "registration response is missing an type entry");
        Assert::notEmpty($respBody['transports'], "registration response is missing an transports entry");

        $reqValue = [
            'id' => $respBody['id'],
            'rawId' => $respBody['rawId'],
            'type' => $respBody['type'],
            'response' => [
                'authenticatorData' => $respBody['response']['authenticatorData'],
                'clientDataJSON' => $respBody['response']['clientDataJSON'],
                'attestationObject' => $respBody['response']['attestationObject'],
            ],
            'user' => [
                'displayName' => $user->display_name,
                'id' => $user->id,
                'name' => $user->username,
            ],
            'transports' => $respBody['transports'],
        ];

        $this->cleanRequestBody();
        $this->setRequestBody('employee_id', $this->tempEmployeeId);
        $this->setRequestBody('value', $reqValue);
        $this->mfa = $mfa;
    }


    /**
     * @When I update the MFA
     */
    public function iUpdateTheMfa()
    {
        $this->iRequestTheResourceBe('/mfa/' . $this->mfa->id, self::UPDATED);
    }


    /**
     * @When I update the mfaWebauthn
     */
    public function iUpdateTheMfaWebauthn()
    {
        $this->iRequestTheResourceBe('/mfa/' . $this->mfa->id . '/webauthn/' . $this->mfaWebauthn->id, self::UPDATED);
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
        $this->iRequestTheResourceBe('/mfa/' . $this->mfa->id . '/verify', self::CREATED);
    }

    /**
     * @When I request to verify the webauthn Mfa registration
     */
    public function iRequestToVerifyTheWebauthnMfaRegistration()
    {
        $this->iRequestTheResourceBe('/mfa/' . $this->mfa->id . '/verify/registration', self::CREATED);
    }

    /**
     * @When I request to verify the webauthn Mfa registration with a label of :label
     */
    public function iRequestToVerifyTheWebauthnMfaRegistrationWithALabelOf($label)
    {
        $this->setRequestBody('label', $label);
        $this->iRequestTheResourceBe('/mfa/' . $this->mfa->id . '/verify/registration', self::CREATED);
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
        $this->iRequestTheResourceBe('/mfa/' . $this->mfa->id, self::DELETED);
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
        $this->iRequestTheResourceBe(
            '/mfa/' . $this->mfa->id . '/webauthn/' . $webauthnId,
            self::DELETED
        );
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
        $this->iRequestTheResourceBe('/mfa', self::CREATED);
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
