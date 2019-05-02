<?php
namespace common\components;

use common\components\Emailer;
use common\models\EmailLog;
use common\models\Mfa;
use common\models\MfaBackupcode;
use yii\base\Component;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class MfaBackendManager extends Component implements MfaBackendInterface
{
    /**
     * Initialize a new MFA backend registration
     * @param int $userId
     * @return array
     * @throws \Exception
     */
    public function regInit(int $userId): array
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

        $data = [
            'toAddress' => $mfa->user->manager_email,
            'code' => $code,
            'id' => $mfa->id,
        ];

        $emailer->sendMessageTo(EmailLog::MESSAGE_TYPE_MFA_MANAGER, $mfa->user, $data);
        $emailer->sendMessageTo(EmailLog::MESSAGE_TYPE_MFA_MANAGER_HELP, $mfa->user);
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
        if (! MfaBackupcode::validateAndRemove($mfaId, $value)) {
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
     * @return bool
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function delete(int $mfaId): bool
    {
        return MfaBackupcode::deleteCodesForMfaId($mfaId);
    }
}
