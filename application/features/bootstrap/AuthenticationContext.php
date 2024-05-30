<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use common\models\Mfa;
use common\models\MfaWebauthn;
use common\models\User;
use FeatureContext;
use Webmozart\Assert\Assert;
use Yii;

class AuthenticationContext extends FeatureContext
{
    /**
     * @Given :username has a valid TOTP MFA method
     */
    public function userHasAValidTotpMfaMethod($username)
    {
        $user = User::findByUsername($username);
        Assert::notEmpty($user, 'Unable to find user ' . $username);

        $mfa = new Mfa([
            'user_id' => $user->id,
            'type' => mfa::TYPE_TOTP,
            'verified' => 1,
            'external_uuid' => 'ba3ae9f3-273d-4edb-93eb-021f33434c41',
        ]);
        Assert::true($mfa->save(), 'Failed to add that TOTP MFA record to the database.');

        // TEMP
        $mfa->refresh();
        echo 'totp mfa: ' . var_export($mfa->attributes, true) . PHP_EOL;
    }

    /**
     * @Given :username has a valid WebAuthn MFA method
     */
    public function userHasAValidWebauthnMfaMethod($username)
    {
        $user = User::findByUsername($username);
        Assert::notEmpty($user, 'Unable to find user ' . $username);

        $mfa = new Mfa([
            'user_id' => $user->id,
            'type' => mfa::TYPE_WEBAUTHN,
            'verified' => 1,
            'external_uuid' => '097791bf-2385-4ab4-8b06-14561a338d8e',
        ]);
        Assert::true($mfa->save(), 'Failed to add that WebAuthn MFA record to the database.');

        $webauthn = MfaWebauthn::createWebauthn($mfa, uniqid());

        // TEMP
        echo 'webauthn mfa: ' . var_export($mfa->attributes, true) . PHP_EOL;
        echo 'webauthn: ' . var_export($webauthn->attributes, true) . PHP_EOL;
    }

    /**
     * @Given the WebAuthn MFA API is down
     */
    public function theWebauthnMfaApiIsDown()
    {
        throw new PendingException();
    }
}
