<?php

namespace common\components;

use common\helpers\MySqlDateTime;
use common\models\Mfa;
use common\models\MfaBackupcode;
use common\models\MfaWebauthn;
use yii\base\Component;
use yii\web\BadRequestHttpException;
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

    public function regInit(int $userId, string $mfaExternalUuid = null, string $rpOrigin = '', string $recoveryEmail = ''): array
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
     * @param string $verifyType Only used for WebAuthn
     * @param string $label Only used for WebAuthn
     * @return bool
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    public function verify(int $mfaId, string $value, string $rpOrigin = '', string $verifyType = '', string $label = ''): bool
    {
        if ($verifyType != "") {
            throw new BadRequestHttpException(
                'A non-blank verification type is not allowed when verifying a mfa of type ' . Mfa::TYPE_BACKUPCODE,
                1670950767
            );
        }

        if (MfaBackupcode::validateAndRemove($mfaId, $value)) {
            MfaBackupcode::sendRefreshCodesMessage($mfaId);
            return true;
        }
        return false;
    }

    /**
     * Delete MFA backend configuration
     * @param int $mfaId
     * @param int $childId the id of the related/child object (only used for the WebAuthn backend)
     * @return bool
     * @throws ServerErrorHttpException
     */
    public function delete(int $mfaId, int $childId = 0): bool
    {
        if ($childId != 0) {
            throw new ForbiddenHttpException(
                sprintf("May not delete a MfaWebauthn object on a %s mfa type", Mfa::TYPE_BACKUPCODE),
                1658237140
            );
        }
        return MfaBackupcode::deleteCodesForMfaId($mfaId);
    }

}
