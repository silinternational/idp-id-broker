<?php

namespace common\components;

use common\models\Mfa;
use common\models\MfaWebauthn;
use common\models\User;
use Google\Service\PeopleService\ExternalId;
use GuzzleHttp\Exception\GuzzleException;
use yii\base\Component;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class MfaBackendWebAuthn extends Component implements MfaBackendInterface
{
    /**
     * @var string
     * This and most of the following attributes are hydrated by application/common/config/main.php
     *   based on the entry: 'webauthn' => ArrayHelper::merge(...
     *   which pulls in the environment variables with the prefix `MFA_WEBAUTHN_`,
     *   e.g. MFA_WEBAUTHN_apiBaseUrl
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
        if ($this->apiBaseUrl == "") {
            throw new \InvalidArgumentException("MFABackendWebAuthn class must not have a blank apiBaseUrl.");
        }
        $this->client = new MfaApiClient($this->apiBaseUrl, $this->apiKey, $this->apiSecret);
        parent::init();
    }

    /**
     * Initialize a new WebAuthn registration
     * @param int $userId The User ID
     * @param string $mfaExternalUuid The User ID
     * @param string $rpOrigin The Relying Party Origin URL (with scheme, without port or path)
     * @return array JSON decoded object to be passed to browser credential create API for WebAuthn dance
     * @throws GuzzleException
     */
    public function regInit(int $userId, string $mfaExternalUuid = null, string $rpOrigin = '', string $recoveryEmail = ''): array
    {
        $user = User::findOne(['id' => $userId]);
        if ($user == null) {
            return [];
        }

        $headers = $this->getWebAuthnHeaders($user->username, $user->getDisplayName(), $rpOrigin, $mfaExternalUuid);

        return $this->client->webauthnCreateRegistration($headers);
    }

    /**
     * Initialize authentication sequence
     * @param int $mfaId The MFA ID
     * @param string $rpOrigin The Relying Party Origin URL (with scheme, without port or path)
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
     * Verify response from user is correct for the MFA backend device.
     * @param int $mfaId The MFA ID
     * @param string|array $value The stringified JSON response from the browser credential api
     * @param string $rpOrigin The Replay Party Origin URL (with scheme, without port or path)
     * @param string $verifyType The type of verification: either "registration" or assumed to be for login
     * @param string $label The label of the new webauthn
     * @return bool|string
     * @throws GuzzleException
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     * @throws NotFoundHttpException
     */
    public function verify(int $mfaId, $value, string $rpOrigin = '', string $verifyType = '', string $label = '')
    {
        if ($verifyType != "" && $verifyType != Mfa::VERIFY_REGISTRATION) {
            throw new BadRequestHttpException(
                'A non-blank verification type for a ' . Mfa::TYPE_WEBAUTHN . " may only be: " . Mfa::VERIFY_REGISTRATION,
                1671016320
            );
        }

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


        if (!is_array($value)) {
            $value = Json::decode($value);
            if ($value == null) {
                throw new ServerErrorHttpException("Missing data or unable to decode as JSON", 1638447364);
            }
        }

        // If the verifyType is not registration, finish the login process.
        if ($verifyType != Mfa::VERIFY_REGISTRATION) {
            $webauthnCount = $mfa->getMfaWebauthns()->count();
            if ($webauthnCount < 1) {
                throw new NotFoundHttpException("No MfaWebauthn records found for Mfa ID: " . $mfa->id, 1659637860);
            }

            $response = $this->client->webauthnValidateAuthentication($headers, $value);
            $khh = $response['key_handle_hash'];

            $mfaWebauthn = MfaWebauthn::findOne(['key_handle_hash' => $khh, 'mfa_id' => $mfaId]);
            if ($mfaWebauthn == null) {
                $mfaWebauthn = MfaWebauthn::findOne(['key_handle_hash' => 'u2f', 'mfa_id' => $mfaId]);
                if ($mfaWebauthn == null) {
                    \Yii::error([
                        'action' => 'update MfaWebauthn last_used_utc',
                        'status' => 'error',
                        'mfa_id' => $mfaId,
                        'error' => 'No MfaWebauthn record with key_handle_hash: "' . $khh . '" or  "u2f"',
                    ]);
                }
            }

            if ($mfaWebauthn != null) {
                $mfaWebauthn->setLastUsed();
            }

            return true;
        }

        // Assume a new webauthn was requested and finish its registration process
        try {
            $results = $this->client->webauthnValidateRegistration($headers, $value);
        } catch (GuzzleException $e) {
            throw new HttpException($e->getCode(), $e->getMessage(), 1660660611);
        }

        if (!isset($results['key_handle_hash'])) {
            return false;
        }

        MfaWebauthn::createWebauthn($mfa, $results['key_handle_hash'], $label);
        $mfa->setVerified();
        return true;
    }


    /**
     * Delete WebAuthn credential
     * @param int $mfaId
     * @param int $childId the id of the related/child object (only used for the WebAuthn backend)
     * @return bool
     * @throws NotFoundHttpException
     * @throws GuzzleException
     */
    public function delete(int $mfaId, int $childId = 0): bool
    {
        $mfa = Mfa::findOne(['id' => $mfaId]);
        if ($mfa == null) {
            throw new NotFoundHttpException("MFA record for given ID not found");
        }

        // If not verified and there is no external_uuid, just delete this local mfa entry
        if (!$mfa->verified && empty($mfa->external_uuid)) {
            // Let the $mfa->delete() process continue without further processing here
            return true;
        } elseif (empty($mfa->external_uuid)) {
            throw new ForbiddenHttpException("May not delete a verified webauthn backend without an external_uuid", 1658237150);
        }

        $headers = $this->getWebAuthnHeaders(
            $mfa->user->username,
            $mfa->user->getDisplayName(),
            '',
            $mfa->external_uuid
        );

        // No child Id was provided, so try to delete the backend webauthn container object
        if ($childId == 0) {
            return $this->client->webauthnDelete($headers);
        }
        return self::deleteWebAuthn($mfa, $childId, $headers);
    }

    // Deletes a webauthn entry both in the api backend and in the local database.
    // If no webauthn entries are left, it attempts to delete the parent mfa object
    //   both in the api backend and in the local database.
    private function deleteWebAuthn(Mfa $mfa, int $webauthnId, array $headers): bool
    {
        $webauthn = MfaWebauthn::findOne([
            'mfa_id' => $mfa->id,
            'id' => $webauthnId,
        ]);
        if (empty($webauthn)) {
            throw new NotFoundHttpException(
                "MfaWebauthn not found with id: $webauthnId and mfa_id: $mfa->id",
                1670950790
            );
        }

        if (!$this->client->webauthnDeleteCredential($webauthn->key_handle_hash, $headers)) {
            throw new ServerErrorHttpException(
                sprintf("Unable to delete existing backend webauthn key. [id=%s]", $webauthn->id),
                1658237200
            );
        }

        // Now delete this local webauthn entry
        if ($webauthn->delete() === false) {
            \Yii::error([
                'action' => 'mfa-delete-webauthn-for-mfa-id',
                'mfa-type' => Mfa::TYPE_WEBAUTHN,
                'status' => 'error',
                'error' => $webauthn->getFirstErrors(),
            ]);
            throw new ServerErrorHttpException(
                sprintf("Unable to delete existing webauthn mfa. [id=%s]", $webauthn->id),
                1658237300
            );
        }

        $existingCount = $mfa->getMfaWebauthns()->Count();

        // If there are no more webauthn entries for this mfa, try to delete the backend webauthn container object
        if ($existingCount < 1) {
            $mfaBackEnd = mfa::getBackendForType($mfa->type);
            $didDelete = $mfaBackEnd->delete($mfa->id);

            if ($didDelete) {
                $mfa->delete();
            } else {
                Yii::warning("unable to delete external mfa api webauthn entry with id: $mfa->external_uuid");
            }

        }

        return true;
    }


    /**
     * The WebAuthn API requires a bunch of headers, this method returns the parameters as an array of
     * headers to be included with API calls.
     * @param string $username The user's username
     * @param string $displayName The user's display name
     * @param string $rpOrigin The Relying Party Origin URL (with scheme, without port or path)
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
