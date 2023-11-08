<?php

namespace common\models;

use common\helpers\MySqlDateTime;
use common\components\Emailer;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;

/**
 * Class MfaBackupcode
 * @package common\models
 * @method MfaBackupcode self::findOne()
 */
class MfaBackupcode extends MfaBackupcodeBase
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
     * Check if given value exists, if so delete and return true, else false
     * @param int $mfaId
     * @param string $code
     * @return bool
     * @throws ServerErrorHttpException
     */
    public static function validateAndRemove(int $mfaId, $code): bool
    {
        $intCode = intval($code);
        $codeStartsWithZero = (substr($code, 0, 1) === '0');

        $backupCodes = MfaBackupcode::findAll(['mfa_id' => $mfaId]);
        foreach ($backupCodes as $backupCode) {
            $foundMatch = false;

            /*
             *   For backwards compatability, check if the integer version
             * of the code matches (i.e. without leading zeros) what is
             * in the database.
             *   In earlier versions, codes were being cast to integers,
             * and some shortened values may still be in the database.
             */
            if ($codeStartsWithZero) {
                $foundMatch = password_verify($intCode, $backupCode->value);
            }

            if ($foundMatch || password_verify($code, $backupCode->value)) {
                if ($backupCode->delete() === false) {
                    \Yii::error([
                        'action' => 'mfa-validate-and-remove',
                        'mfa-type' => Mfa::TYPE_BACKUPCODE,
                        'status' => 'error',
                        'error' => $backupCode->getFirstErrors(),
                    ]);
                    throw new ServerErrorHttpException("Unable to delete code after use", 1506692863);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Generate new backup codes
     * @param int $mfaId
     * @param int $howMany
     * @return array
     * @throws ServerErrorHttpException
     */
    public static function createBackupCodes(int $mfaId, int $howMany = 10): array
    {
        // Generate and store new codes
        $clearTextCodes = [];
        for ($i = 0; $i < $howMany; $i++) {
            $code = substr(random_int(100000000, 200000000), 1);
            $clearTextCodes[] = $code;
            self::insertBackupCode($mfaId, $code);
        }

        // Return array of clear text codes
        return $clearTextCodes;
    }

    /**
     * @param int $mfaId
     * @param string $value
     * @throws ServerErrorHttpException
     */
    public static function insertBackupCode(int $mfaId, $value)
    {
        $code = new MfaBackupcode();
        $code->mfa_id = $mfaId;
        $code->value = password_hash($value, PASSWORD_DEFAULT);
        if (!$code->save()) {
            \Yii::error([
                'action' => 'mfa-insert-backup-code',
                'mfa-type' => Mfa::TYPE_BACKUPCODE,
                'status' => 'error',
                'error' => $code->getFirstErrors(),
            ]);
            throw new ServerErrorHttpException(
                "Unable to save new backup code, error: " . print_r($code->getFirstErrors(), true),
                1506692503
            );
        }
    }

    /**
     * @param int $mfaId
     * @return bool
     * @throws ServerErrorHttpException
     */
    public static function deleteCodesForMfaId(int $mfaId): bool
    {
        $existing = self::find()->where(['mfa_id' => $mfaId])->all();
        if ($existing) {
            foreach ($existing as $entry) {
                if ($entry->delete() === false) {
                    \Yii::error([
                        'action' => 'mfa-delete-codes-for-mfa-id',
                        'mfa-type' => Mfa::TYPE_BACKUPCODE,
                        'status' => 'error',
                        'error' => $entry->getFirstErrors(),
                    ]);
                    throw new ServerErrorHttpException(
                        sprintf("Unable to delete existing code. [id=%s]", $entry->id),
                        1506692863
                    );
                }
            }
        }
        return true;
    }

    /**
     * @param int $mfaId
     */
    public static function sendRefreshCodesMessage(int $mfaId): void
    {
        $numCodesRemaining = MfaBackupcode::find()->where(['mfa_id' => $mfaId])->count();

        /* @var $emailer Emailer */
        $emailer = \Yii::$app->emailer;
        if ($emailer->shouldSendRefreshBackupCodesMessage($numCodesRemaining)) {
            $mfa = Mfa::findOne($mfaId);
            $user = $mfa->user;
            $emailer->sendMessageTo(EmailLog::MESSAGE_TYPE_REFRESH_BACKUP_CODES, $user);
        }
    }
}
