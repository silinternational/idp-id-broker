<?php
namespace common\models;

use common\helpers\MySqlDateTime;
use common\components\Emailer;
use yii\helpers\ArrayHelper;
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
        ], parent::rules());
    }


    /**
     * @param int $mfaId
     * @param string $keyHandleHash
     * @return bool
     * @throws ServerErrorHttpException
     */
    public static function deleteCredentialForMfaId(int $mfaId, string $keyHandleHash): bool
    {
        $existing = self::find()->where(['mfa_id' => $mfaId, 'key_handle_hash' => $keyHandleHash])->all();
        if (!$existing) {
            return false;
        }

        $mfa = Mfa::findOne($mfaId);
        $didDelete = false;

        // Normally, there should just be one entry. Just to be safe, this assumes
        // there could be more than one.  It deletes the matching backend entry of the
        // first one as well as deleting all the local entries.
        foreach ($existing as $entry) {
            // Attempt to delete the matching backend entry for this webauthn key
            // but only once
            if (!$didDelete) {
                $didDelete = $mfa->deleteBackendCredential($keyHandleHash);

                if (!$didDelete) {
                    throw new ServerErrorHttpException(
                        sprintf("Unable to delete existing backend webauthn key. [id=%s]", $entry->id),
                        1658237200
                    );
                }
            }

            // Now delete this local webauthn entry
            if ($entry->delete() === false) {
                \Yii::error([
                    'action' => 'mfa-delete-webauthn-for-mfa-id',
                    'mfa-type' => Mfa::TYPE_WEBAUTHN,
                    'status' => 'error',
                    'error' => $entry->getFirstErrors(),
                ]);
                throw new ServerErrorHttpException(
                    sprintf("Unable to delete existing webauthn mfa. [id=%s]", $mfaId),
                    1658237300
                );
            }
        }

        // If there are no more webauthn entries for this mfa, try to delete the backend webauthn container object
        $existing = self::find()->where(['mfa_id' => $mfaId])->all();
        if (!$existing) {
            $mfaBackEnd = mfa::getBackendForType($mfa->type);
            return $mfaBackEnd->delete($mfaId);
        }

        return true;
    }
}
