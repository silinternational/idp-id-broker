<?php

namespace common\components;

use common\models\Mfa;
use common\models\User;
use yii\base\Component;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class MfaBackendTotp extends Component implements MfaBackendInterface
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
     * @var string
     */
    public string $issuer;

    /**
     * @var MfaApiClient
     */
    public MfaApiClient $client;

    public function init()
    {
        $this->client = new MfaApiClient($this->apiBaseUrl, $this->apiKey, $this->apiSecret);
        parent::init();
    }

    public function regInit(int $userId, string $mfaExternalUuid = null, string $rpOrigin = '', string $recoveryEmail = ''): array
    {
        $user = User::findOne(['id' => $userId]);
        if ($user == null) {
            throw new NotFoundHttpException("User not found when trying to create new TOTP configuration");
        }

        return $this->client->createTotp($user->username, \Yii::$app->params['idpDisplayName']);
    }

    /**
     * Initialize authentication sequence
     * @param int $mfaId
     * @param string $rpOrigin
     * @return array
     */
    public function authInit(int $mfaId, string $rpOrigin = ''): array
    {
        return [];
    }

    /**
     * Verify response from user is correct for the MFA backend device
     * @param int $mfaId The MFA ID
     * @param string $value Value provided by user, such as TOTP number or WebAuthn challenge response
     * @param string $rpOrigin
     * @param string $verifyType Only used for WebAuthn
     * @param string $label Only used for WebAuthn
     * @return bool
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws \Exception
     */
    public function verify(int $mfaId, string $value, string $rpOrigin = '', string $verifyType = '', string $label = ''): bool
    {
        if ($verifyType != "") {
            throw new BadRequestHttpException(
                'A non-blank verification type is not allowed when verifying a mfa of type ' . Mfa::TYPE_TOTP,
                1670950780
            );
        }

        $mfa = Mfa::findOne(['id' => $mfaId]);
        if ($mfa == null) {
            throw new NotFoundHttpException('MFA configuration not found');
        }

        if ($this->client->validateTotp($mfa->external_uuid, $value)) {
            if ($mfa->verified !== 1) {
                $mfa->verified = 1;
                if (!$mfa->save()) {
                    throw new ServerErrorHttpException();
                }
            }

            return true;
        }
        return false;
    }

    /**
     * Delete MFA backend configuration
     * @param int $mfaId
     * @param int $childId the id of the related/child object (only used for the WebAuthn backend)
     * @return bool
     * @throws NotFoundHttpException
     */
    public function delete(int $mfaId, int $childId = 0): bool
    {
        if ($childId != 0) {
            throw new ForbiddenHttpException(
                sprintf("May not delete a MfaWebauthn object on a %s mfa type", Mfa::TYPE_TOTP),
                1658237130
            );
        }

        $mfa = Mfa::findOne(['id' => $mfaId]);
        if ($mfa == null) {
            throw new NotFoundHttpException('MFA configuration not found');
        }

        if (is_string($mfa->external_uuid)) {
            return $this->client->deleteTotp($mfa->external_uuid);
        }

        return true;
    }

}
