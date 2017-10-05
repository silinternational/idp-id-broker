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
    public $apiBaseUrl;

    /**
     * @var string
     */
    public $apiKey;

    /**
     * @var string
     */
    public $apiSecret;

    /**
     * @var MfaApiClient
     */
    public $client;

    /**
     * @var string
     */
    public $appId;

    public function init()
    {
        $this->client = new MfaApiClient($this->apiBaseUrl, $this->apiKey, $this->apiSecret);
        parent::init();
    }

    /**
     * Initialize a new MFA backend registration
     * @param int $userId
     * @return array
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function regInit(int $userId): array
    {
        $user = User::findOne(['id' => $userId]);
        if ($user == null) {
            throw new NotFoundHttpException("User not found when trying to create new TOTP configuration");
        }

        $response = $this->client->u2fCreateRegistration($this->appId);

        $mfa = new Mfa();
        $mfa->user_id = $userId;
        $mfa->type = Mfa::TYPE_U2F;
        $mfa->external_uuid = $response['uuid'];
        $mfa->verified = 0;
        if ( ! $mfa->save()) {
            throw new ServerErrorHttpException(
                "Unable to save new U2F configuration. Error: " . print_r($mfa->getFirstErrors(), true)
            );
        }

        unset($response['uuid']);

        return [
            'id' => $mfa->id,
            'data' => $response,
        ];
    }

    /**
     * Initialize authentication sequence
     * @param int $mfaId
     * @return array
     * @throws NotFoundHttpException
     */
    public function authInit(int $mfaId): array
    {
        $mfa = Mfa::findOne(['id' => $mfaId]);
        if ($mfa == null) {
            throw new NotFoundHttpException("MFA record for given ID not found");
        }

        $response = $this->client->u2fCreateAuthentication($mfa->external_uuid);

        return [
            'id' => $mfa->id,
            'data' => $response,
        ];
    }

    /**
     * Verify response from user is correct for the MFA backend device
     * @param int $mfaId The MFA ID
     * @param string $value Value provided by user, such as TOTP number or U2F challenge response
     * @return bool
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function verify(int $mfaId, string $value): bool
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
                if ( ! $mfa->save()) {
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
     * @throws ServerErrorHttpException
     */
    public function delete(int $mfaId)
    {
        $mfa = Mfa::findOne(['id' => $mfaId]);
        if ($mfa == null) {
            throw new NotFoundHttpException("MFA record for given ID not found");
        }

        if ($this->client->u2fDelete($mfa->external_uuid)) {
            if ($mfa->delete() !== false) {
                return true;
            }
        }

        throw new ServerErrorHttpException("Unable to delete U2F record");
    }
}