<?php
namespace common\components;

use common\models\Mfa;
use common\models\MfaBackupcode;
use yii\base\Component;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class MfaBackendBackupcode extends Component implements MfaBackendInterface
{
    /**
     * Number of backup codes to generate
     * @var int
     */
    public $numBackupCodes = 10;

    /**
     * Initialize a new MFA backend registration
     * @param int $userId
     * @return array
     * @throws ServerErrorHttpException
     */
    public function regInit(int $userId): array
    {
        // Check if user already has backup codes mfa backend to use
        $mfa = Mfa::findOne(['user_id' => $userId, 'type' => Mfa::TYPE_BACKUPCODE]);
        if ($mfa === null) {
            $mfa = new Mfa();
            $mfa->type = Mfa::TYPE_BACKUPCODE;
            $mfa->user_id = $userId;
            $mfa->verified = 1;
            if ( ! $mfa->save()) {
                \Yii::error([
                    'action' => 'mfa-reg-init',
                    'mfa-type' => Mfa::TYPE_BACKUPCODE,
                    'status' => 'error',
                    'error' => $mfa->getFirstErrors(),
                ]);
                throw new ServerErrorHttpException("Unable to save new mfa record, error: " . print_r($mfa->getFirstErrors(), true), 1506695238);
            }
        }

        $codes = MfaBackupcode::createBackupCodes($mfa->id, $this->numBackupCodes);

        return [
            'id' => $mfa->id,
            'data' => $codes,
        ];
    }

    /**
     * Initialize authentication sequence
     * @param int $mfaId
     * @return array
     */
    public function authInit(int $mfaId): array
    {
        return [];
    }

    /**
     * Verify response from user is correct for the MFA backend device
     * @param int $mfaId The MFA ID
     * @param string $value Value provided by user, such as TOTP number or U2F challenge response
     * @return bool
     * @throws ServerErrorHttpException
     */
    public function verify(int $mfaId, $value): bool
    {
        return MfaBackupcode::validateAndRemove($mfaId, $value);
    }

    /**
     * Delete MFA backend configuration
     * @param int $mfaId
     * @return bool
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function delete(int $mfaId): bool
    {
        $mfa = Mfa::findOne(['id' => $mfaId]);
        if ($mfa == null) {
            throw new NotFoundHttpException();
        }

        if ($mfa->delete() !== false) {
            return true;
        }

        throw new ServerErrorHttpException("Unable to delete Backup Codes configuration");
    }
}
