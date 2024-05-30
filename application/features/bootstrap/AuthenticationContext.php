<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use common\models\Mfa;
use common\models\MfaWebauthn;
use common\models\User;
use FeatureContext;
use stdClass;
use Webmozart\Assert\Assert;

class AuthenticationContext extends FeatureContext
{
    /**
     * @Given :username has a valid WebAuthn MFA method
     */
    public function userHasAValidWebauthnMfaMethod($username)
    {
        $user = User::findByUsername($username);
        Assert::notEmpty($user, 'Unable to find user ' . $username);

        $creationResult = Mfa::create($user->id, Mfa::TYPE_WEBAUTHN);
        $mfa = Mfa::findOne(['id' => $creationResult['id']]);
        $publicKey = $creationResult['data']['publicKey'];
        $rpId = $publicKey['rp']['id'];

        $this->cleanRequestBody();
        $this->setRequestBody('challenge', $publicKey['challenge']);
        $this->setRequestBody('relying_party_id', $rpId);
        $this->callU2fSimulator('/u2f/registration', 'created', $user, $mfa->external_uuid);
        $u2fSimResponse = $this->getResponseBody();

        if (isset($u2fSimResponse['clientExtensionResults']) && empty($u2fSimResponse['clientExtensionResults'])) {
            // Force JSON-encoding to treat this as an empty object, not an empty array.
            $u2fSimResponse['clientExtensionResults'] = new stdClass();
        }

        $mfaVerifyResult = $mfa->verify(
            $u2fSimResponse,
            $rpId,
            'registration'
        );
        Assert::true($mfaVerifyResult, 'Failed to verify the WebAuthn MFA');

        // TEMP
        echo 'Please stop the WebAuthn MFA API now.' . PHP_EOL;
    }

    /**
     * @Given the WebAuthn MFA API is down
     */
    public function theWebauthnMfaApiIsDown()
    {
        // Idea: Set the password this app has for the WebAuthn MFA (u2fsim) to an incorrect value.
        sleep(5); // TEMP
        echo '(Proceeding with the test...)' . PHP_EOL;
//        throw new PendingException();
    }
}
