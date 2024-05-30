<?php

namespace Sil\SilIdBroker\Behat\Context;

use Aws\DynamoDb\DynamoDbClient;
use common\models\Mfa;
use common\models\User;
use FeatureContext;
use Sil\PhpEnv\Env;
use stdClass;
use Webmozart\Assert\Assert;

class AuthenticationContext extends FeatureContext
{
    public function __destruct()
    {
        // Ensure the (local) WebAuthn MFA API is left with the correct API Secret.
        $this->setWebAuthnApiSecretTo(Env::get('MFA_WEBAUTHN_apiSecret'));
    }

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
    }

    /**
     * @Given we have the wrong password for the WebAuthn MFA API
     */
    public function weHaveTheWrongPasswordForTheWebauthnMfaApi()
    {
        /* This is setting the API secret to something else, so that the one
         * ID Broker has is NOT correct anymore. */
        $this->setWebAuthnApiSecretTo('something different');
    }

    protected function setWebAuthnApiSecretTo(string $newPlainTextApiSecret)
    {
        $newHashedApiSecret = password_hash($newPlainTextApiSecret, PASSWORD_DEFAULT);
        $dynamoDbClient = new DynamoDbClient([
            'region'   => getenv('AWS_DEFAULT_REGION'),
            'endpoint' => getenv('AWS_ENDPOINT'),
            'disableSSL' => true,
            'version' => '2012-08-10',
        ]);
        $dynamoDbClient->updateItem([
            'Key' => [
                'value' => [
                    'S' => Env::get('MFA_WEBAUTHN_apiKey'),
                ],
            ],
            'UpdateExpression' => 'set hashedApiSecret = :newHashedApiSecret',
            'ExpressionAttributeValues' => [
                ':newHashedApiSecret' => [
                    'S' => $newHashedApiSecret,
                ],
            ],
            'TableName' => Env::get('API_KEY_TABLE'),
        ]);
    }

    /**
     * @Given we have the right password for the WebAuthn MFA API
     */
    public function weHaveTheRightPasswordForTheWebauthnMfaApi()
    {
        $this->setWebAuthnApiSecretTo(Env::get('MFA_WEBAUTHN_apiSecret'));
    }
}
