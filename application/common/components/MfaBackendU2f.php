<?php
namespace common\components;

use common\models\Mfa;
use common\models\User;
use yii\base\Component;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class MfaBackendU2f extends Component implements MfaBackendInterface
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

    public function init()
    {
        $this->client = new MfaApiClient($this->apiBaseUrl, $this->apiKey, $this->apiSecret);
        parent::init();
    }

    /**
     * Initialize a new MFA backend registration
     * @param int $userId
     * @param string $rpOrigin
     * @return array
     */
    public function regInit(int $userId, string $rpOrigin = ''): array
    {
        return $this->client->u2fCreateRegistration($this->appId);
    }

    /**
     * Initialize authentication sequence
     * @param int $mfaId
     * @param string $rpOrigin
     * @return array
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public function authInit(int $mfaId, string $rpOrigin = ''): array
    {
        $mfa = Mfa::findOne(['id' => $mfaId]);
        if ($mfa == null) {
            throw new NotFoundHttpException("MFA record for given ID not found");
        }

        $response = $this->client->u2fCreateAuthentication($mfa->external_uuid);

        unset($response['uuid']);

        return $response;
    }

    /**
     * Verify response from user is correct for the MFA backend device
     * @param int $mfaId The MFA ID
     * @param string $value Value provided by user, such as TOTP number or U2F challenge response
     * @param string $rpOrigin
     * @return bool
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function verify(int $mfaId, string $value, string $rpOrigin = ''): bool
    {
        $mfa = Mfa::findOne(['id' => $mfaId]);
        if ($mfa == null) {
            throw new NotFoundHttpException("MFA record for given ID not found");
        }

        if ($mfa->verified === 1) {
            return $this->client->u2fValidateAuthentication($mfa->external_uuid, $value);
        } else {
            if ($this->client->u2fValidateRegistration($mfa->external_uuid, $value)) {
                $mfa->verified = 1;
                if (! $mfa->save()) {
                    throw new ServerErrorHttpException(
                        "Unable to save U2F record after verification. Error: " . print_r($mfa->getFirstErrors(), true)
                    );
                }
                return true;
            }
            return false;
        }
    }

    /**
     * Delete U2F configuration
     * @param int $mfaId
     * @return bool
     * @throws NotFoundHttpException
     */
    public function delete(int $mfaId): bool
    {
        $mfa = Mfa::findOne(['id' => $mfaId]);
        if ($mfa == null) {
            throw new NotFoundHttpException("MFA record for given ID not found");
        }

        if (is_string($mfa->external_uuid)) {
            return $this->client->u2fDelete($mfa->external_uuid);
        }

        return true;
    }
}
