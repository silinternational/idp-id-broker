<?php
namespace common\components;

use common\helpers\MySqlDateTime;
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
     * @throws \Exception
     */
    public function regInit(int $userId): array
    {
        // Get existing MFA record for backupcode to create/update codes for
        $mfa = Mfa::findOne(['user_id' => $userId, 'type' => Mfa::TYPE_BACKUPCODE]);
        if ($mfa === null) {
            throw new \Exception("A backupcode MFA record does not exist for this user", 1507904428);
        }

        // cheap solution to the problem of reporting an old date for new codes
        $mfa->created_utc = MySqlDateTime::now();
        $mfa->save();

        $mfa->setVerified();

        MfaBackupcode::deleteCodesForMfaId($mfa->id);

        return MfaBackupcode::createBackupCodes($mfa->id, $this->numBackupCodes);
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
     * @throws \Exception
     */
    public function verify(int $mfaId, $value): bool
    {
        if (MfaBackupcode::validateAndRemove($mfaId, $value)) {
            MfaBackupcode::sendRefreshCodesMessage($mfaId);
            return true;
        }
        return false;
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
        return MfaBackupcode::deleteCodesForMfaId($mfaId);
    }
}
