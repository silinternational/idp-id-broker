<?php
namespace common\components;

use common\models\Mfa;
use common\models\User;
use GuzzleHttp\Exception\GuzzleException;
use yii\base\Component;
use yii\helpers\Json;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class MfaBackendWebAuthn extends Component implements MfaBackendInterface
{
    /**
     * @var string
     */
    public string $apiBaseUrl;

    /**
     * @var string
     */
    public string $apiKey;

    /**
     * @var string
     */
    public string $apiSecret;

    /**
     * @var MfaApiClient
     */
    public MfaApiClient $client;

    /**
     * @var string
     */
    public string $appId;

    /**
     * @var string
     */
    public string $rpDisplayName;

    /**
     * @var string
     */
    public string $rpId;

    public function init()
    {
        $this->client = new MfaApiClient($this->apiBaseUrl, $this->apiKey, $this->apiSecret);
        parent::init();
    }

    /**
     * Initialize a new WebAuthn registration
     * @param int $userId The User ID
     * @param string $rpOrigin The Replay Party Origin URL (with scheme, without port or path)
     * @return array JSON decoded object to be passed to browser credential create API for WebAuthn dance
     * @throws GuzzleException
     */
    public function regInit(int $userId, string $rpOrigin = ''): array
    {
        $user = User::findOne(['id' => $userId]);
        if ($user == null) {
            return [];
        }

        $headers = $this->getWebAuthnHeaders($user->username, $user->getDisplayName(), $rpOrigin);

        return $this->client->webauthnCreateRegistration($headers);
    }

    /**
     * Initialize authentication sequence
     * @param int $mfaId The MFA ID
     * @param string $rpOrigin The Replay Party Origin URL (with scheme, without port or path)
     * @return array
     * @throws NotFoundHttpException
     * @throws GuzzleException
     */
    public function authInit(int $mfaId, string $rpOrigin = ''): array
    {
        $mfa = Mfa::findOne(['id' => $mfaId]);
        if ($mfa == null) {
            throw new NotFoundHttpException("MFA record for given ID not found");
        }

        $headers = $this->getWebAuthnHeaders(
            $mfa->user->username,
            $mfa->user->getDisplayName(),
            $rpOrigin,
            $mfa->external_uuid
        );

        return $this->client->webauthnCreateAuthentication($headers);
    }

    /**
     * Verify response from user is correct for the MFA backend device
     * @param int $mfaId The MFA ID
     * @param string $value The stringified JSON response from the browser credential api
     * @param string $rpOrigin The Replay Party Origin URL (with scheme, without port or path)
     * @return bool
     * @throws GuzzleException
     */
    public function verify(int $mfaId, string $value, string $rpOrigin = ''): bool
    {
        $mfa = Mfa::findOne(['id' => $mfaId]);
        if ($mfa == null) {
            throw new NotFoundHttpException("MFA record for given ID not found");
        }

        $headers = $this->getWebAuthnHeaders(
            $mfa->user->username,
            $mfa->user->getDisplayName(),
            $rpOrigin,
            $mfa->external_uuid
        );

        $data = Json::decode($value);
        if ($data == null) {
            throw new ServerErrorHttpException("Missing data or unable to decode as JSON", 1638447364);
        }

        if ($mfa->verified === 1) {
            return $this->client->webauthnValidateAuthentication($headers, $data);
        } else {
            if ($this->client->webauthnValidateRegistration($headers, $data)) {
                $mfa->verified = 1;
                if (! $mfa->save()) {
                    throw new ServerErrorHttpException(
                        "Unable to save WebAuthn record after verification. Error: " . print_r($mfa->getFirstErrors(), true)
                    );
                }
                return true;
            }
            return false;
        }
    }

    /**
     * Delete WebAuthn configuration
     * @param int $mfaId
     * @return bool
     * @throws NotFoundHttpException
     * @throws GuzzleException
     */
    public function delete(int $mfaId): bool
    {
        $mfa = Mfa::findOne(['id' => $mfaId]);
        if ($mfa == null) {
            throw new NotFoundHttpException("MFA record for given ID not found");
        }

        if (is_string($mfa->external_uuid)) {
            return $this->client->webauthnDelete($mfa->external_uuid);
        }

        return true;
    }

    /**
     * The WebAuthn API requires a bunch of headers, this method returns the parameters as an array of
     * headers to be included with API calls.
     * @param string $username The user's username
     * @param string $displayName The user's display name
     * @param string $rpOrigin The Replay Party Origin URL (with scheme, without port or path)
     * @param string|null $uuid If existing credential, this is the UUID originally generated by the WebAuthn API, stored in id-broker as external_id
     * @return array
     */
    private function getWebAuthnHeaders(string $username, string $displayName, string $rpOrigin, string $uuid = null): array
    {
        $headers = [
            'x-mfa-RPDisplayName' => $this->rpDisplayName,
            'x-mfa-RPID' => $this->rpId,
            'x-mfa-RPOrigin' => $rpOrigin,
            'x-mfa-Username' => $username,
            'x-mfa-UserDisplayName' => $displayName,
        ];
        if ($uuid != null) {
            $headers['x-mfa-UserUUID'] = $uuid;
        }

        return $headers;
    }
}
