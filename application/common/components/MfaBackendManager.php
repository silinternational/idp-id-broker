<?php

namespace common\components;

use common\components\Emailer;
use common\models\EmailLog;
use common\models\Mfa;
use common\models\MfaBackupcode;
use yii\base\Component;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class MfaBackendManager extends Component implements MfaBackendInterface
{
    public function regInit(int $userId, string $mfaExternalUuid = null, string $rpOrigin = '', string $recoveryEmail = ''): array
    {
        // Get existing MFA record for manager to create/update codes for
        $mfa = Mfa::findOne(['user_id' => $userId, 'type' => Mfa::TYPE_MANAGER]);
        if ($mfa === null) {
            throw new \Exception("A manager MFA record does not exist for this user", 1507904428);
        }

        $mfa->setVerified();

        $codes = MfaBackupcode::createBackupCodes($mfa->id, 1);
        $this->sendManagerEmail($mfa, $codes[0]);

        /*
         * Don't return the code because it's being sent by email.
         */
        return [];
    }

    /**
     * Send a email message to the manager with the code, and to the user with instructions
     */
    protected function sendManagerEmail($mfa, $code)
    {
        /* @var $emailer Emailer */
        $emailer = \Yii::$app->emailer;

        $emailer->sendMessageTo(
            EmailLog::MESSAGE_TYPE_MFA_RECOVERY,
            $mfa->user,
            [
                'toAddress' => $mfa->user->manager_email,
                'bccAddress' => \Yii::$app->params['mfaManagerBcc'] ?? '',
                'code' => $code,
            ]
        );

        $emailer->sendMessageTo(
            EmailLog::MESSAGE_TYPE_MFA_RECOVERY_HELP,
            $mfa->user,
            [
                'recoveryEmail' => $mfa->user->manager_email,
                'bccAddress' => \Yii::$app->params['mfaManagerHelpBcc'] ?? '',
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
                'A non-blank verification type is not allowed when verifying a mfa of type ' . Mfa::TYPE_MANAGER,
                1670950770
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
                sprintf("May not delete a MfaWebauthn object on a %s mfa type", Mfa::TYPE_MANAGER),
                1658237120
            );
        }
        return MfaBackupcode::deleteCodesForMfaId($mfaId);
    }
}
