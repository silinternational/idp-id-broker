<?php
namespace common\components;

use common\helpers\MySqlDateTime;
use common\models\Mfa;
use common\models\MfaBackupcode;
use yii\base\Component;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class MfaBackendBackupcode extends Component implements MfaBackendInterface
{
    /**
     * Number of backup codes to generate
     * @var int
     */
    public int $numBackupCodes = 10;

    /**
     * Initialize a new MFA backend registration
     * @param int $userId
     * @param string $rpOrigin
     * @return array
     * @throws ServerErrorHttpException
     */
    public function regInit(int $userId, string $rpOrigin = ''): array
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
     * @return bool
     * @throws ServerErrorHttpException
     */
    public function verify(int $mfaId, string $value, string $rpOrigin = ''): bool
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
     * @throws ServerErrorHttpException
     */
    public function delete(int $mfaId): bool
    {
        return MfaBackupcode::deleteCodesForMfaId($mfaId);
    }


    /**
     * Delete credential (only for webauthn)
     * @param int $mfaId
     * @param string $credId
     * @param string $rpOrigin
     * @return bool
     */
    public function deleteCredential(int $mfaId, string $credId): bool
    {
       throw new ForbiddenHttpException("May not delete a credential on a backup code mfa type", 1658237120);
       return false;
    }
}
