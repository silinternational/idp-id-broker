<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use common\models\Mfa;
use common\models\MfaWebauthn;
use common\models\User;
use FeatureContext;
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

        // TEMP
        echo 'webauthn result: ' . json_encode($creationResult, JSON_PRETTY_PRINT) . PHP_EOL;

        $this->cleanRequestBody();
        $this->setRequestBody('challenge', $publicKey['challenge']);
        $this->setRequestBody('relying_party_id', $rpId);
        $this->callU2fSimulator('/u2f/registration', 'created', $user, $mfa->external_uuid);

        $this->iRequestTheResourceBe('/mfa/' . $mfa->id . '/verify/registration', 'created');

        // TEMP
        Assert::true($mfa->refresh(), join("\n", $mfa->getErrorSummary(true)));
        echo 'webauthn mfa: ' . json_encode($mfa->attributes, JSON_PRETTY_PRINT) . PHP_EOL;
    }

    /**
     * @Given the WebAuthn MFA API is down
     */
    public function theWebauthnMfaApiIsDown()
    {
        throw new PendingException();
    }
}
