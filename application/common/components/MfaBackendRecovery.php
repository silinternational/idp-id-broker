<?php

namespace common\components;

use common\components\Emailer;
use common\models\EmailLog;
use common\models\Mfa;
use common\models\MfaBackupcode;
use Sil\EmailService\Client\EmailServiceClientException;
use yii\base\Component;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class MfaBackendRecovery extends Component implements MfaBackendInterface
{
    public function regInit(int $userId, string $mfaExternalUuid = null, string $rpOrigin = ''): array
    {
        // Get existing MFA record for recovery contact to create/update codes for
        $mfa = Mfa::findOne(['user_id' => $userId, 'type' => Mfa::TYPE_RECOVERY]);
        if ($mfa === null) {
            throw new \Exception("A recovery MFA record does not exist for this user", 1742846609);
        }

        $mfa->setVerified();

        $codes = MfaBackupcode::createBackupCodes($mfa->id, 1);
        $this->sendRecoveryEmail($mfa, $codes[0]);

        /*
         * Don't return the code because it's being sent by email.
         */
        return [];
    }

    /**
     * Send a email message to the recovery contact with the code, and to the user with instructions
     * @throws EmailServiceClientException
     */
    protected function sendRecoveryEmail($mfa, $code): void
    {
        /* @var $emailer Emailer */
        $emailer = \Yii::$app->emailer;

        $emailer->sendMessageTo(
            EmailLog::MESSAGE_TYPE_MFA_RECOVERY,
            $mfa->user,
            [
                'toAddress' => $mfa->recovery_email,
                'bccAddress' => \Yii::$app->params['mfaRecoveryBcc'] ?? '',
                'code' => $code,
            ]
        );


        $emailer->sendMessageTo(
            EmailLog::MESSAGE_TYPE_MFA_RECOVERY_HELP,
            $mfa->user,
            [
                'recoveryEmail' => $mfa->recovery_email,
                'bccAddress' => \Yii::$app->params['mfaRecoveryHelpBcc'] ?? '',
            ]
        );
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
                'A non-blank verification type is not allowed when verifying a mfa of type ' . Mfa::TYPE_RECOVERY,
                1742846880
            );
        }

        if (!MfaBackupcode::validateAndRemove($mfaId, $value)) {
            return false;
        }

        $mfa = Mfa::findOne(['id' => $mfaId]);
        if ($mfa === null) {
            throw new \Exception("MFA record not found", 1547074716);
        }

        return true;
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
                sprintf("May not delete a MfaWebauthn object on a %s mfa type", Mfa::TYPE_RECOVERY),
                1742846887
            );
        }
        return MfaBackupcode::deleteCodesForMfaId($mfaId);
    }
}
