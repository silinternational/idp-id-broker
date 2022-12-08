<?php
namespace common\models;

use common\helpers\MySqlDateTime;
use common\components\Emailer;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
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
                'label', 'required'
            ],
            [
                'created_utc', 'default', 'value' => MySqlDateTime::now(),
            ],
        ], parent::rules());
    }


    /**
     * Create a new webauthn entry locally
     * @param Mfa $mfa
     * @param string $keyHandleHash
     * @return array
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    public static function createWebauthn(Mfa $mfa, string $keyHandleHash, string $label=""): MfaWebauthn
    {
        if ($mfa->type != Mfa::TYPE_WEBAUTHN) {
            throw new BadRequestHttpException(
                "Only a Webauthn type Mfa may create a MfaWebauthn child object, not " . $mfa->type,
                1660656677
            );
        }

        $num = count($mfa->mfaWebauthns) + 1;
        $label =  $label ?: $mfa->getReadableType() . '-' . $num;
        $webauthn = new MfaWebauthn();
        $webauthn->mfa_id = $mfa->id;
        $webauthn->label = $label;
        $webauthn->key_handle_hash = $keyHandleHash;
        if (! $webauthn->save()) {
            \Yii::error([
                'action' => 'mfa-create-webauthn',
                'mfa-type' => $mfa->type,
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
