<?php
namespace common\models;

use common\helpers\MySqlDateTime;
use common\components\Emailer;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class MfaWebauthn
 * @package common\models
 * @method MfaWebauthn self::findOne()
 */
class MfaWebauthn extends MfaWebauthnBase
{
    public function rules(): array
    {
        return ArrayHelper::merge([
            [
                'created_utc', 'default', 'value' => MySqlDateTime::now(),
            ],
            [
                'verified', 'default', 'value' => 0,
            ],
        ], parent::rules());
    }


    /**
     * Create a new webauthn entry locally
     * @param int $mfaId
     * @param string $keyHandleHash
     * @return array
     * @throws ServerErrorHttpException
     */
    public static function createWebauthn(int $mfaId, string $keyHandleHash, string $label=""): MfaWebauthn
    {
        $label = $label ?: Mfa::DEFAULT_WEBAUTHN_LABEL;
        $webauthn = new MfaWebauthn();
        $webauthn->mfa_id = $mfaId;
        $webauthn->label = $label;
        $webauthn->key_handle_hash = $keyHandleHash;
        if (! $webauthn->save()) {
            \Yii::error([
                'action' => 'mfa-create-webauthn',
                'mfa-type' => Mfa::TYPE_WEBAUTHN,
                'status' => 'error',
                'error' => $webauthn->getFirstErrors(),
            ]);
            throw new ServerErrorHttpException(
                "Unable to save new webauthn entry, error: " . print_r($webauthn->getFirstErrors(), true),
                1659374000
            );
        }

        return $webauthn;
    }
}
