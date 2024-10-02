<?php

namespace common\models;

use common\helpers\MySqlDateTime;
use Yii;
use yii\helpers\ArrayHelper;

class EmailLog extends EmailLogBase
{
    /* Valid message_type values.
     *
     * NOTE: Changes must be made here, in the `getMessageTypes()` method below,
     *       and in the email_log.message_type enum list in the database.
     */
    public const MESSAGE_TYPE_INVITE = 'invite';
    public const MESSAGE_TYPE_MFA_RATE_LIMIT = 'mfa-rate-limit';
    public const MESSAGE_TYPE_PASSWORD_CHANGED = 'password-changed';
    public const MESSAGE_TYPE_WELCOME = 'welcome';
    public const MESSAGE_TYPE_EXT_GROUP_SYNC_ERRORS = 'ext-group-sync-errors';
    public const MESSAGE_TYPE_GET_BACKUP_CODES = 'get-backup-codes';
    public const MESSAGE_TYPE_REFRESH_BACKUP_CODES = 'refresh-backup-codes';
    public const MESSAGE_TYPE_LOST_SECURITY_KEY = 'lost-security-key';
    public const MESSAGE_TYPE_MFA_OPTION_ADDED = 'mfa-option-added';
    public const MESSAGE_TYPE_MFA_OPTION_REMOVED = 'mfa-option-removed';
    public const MESSAGE_TYPE_MFA_ENABLED = 'mfa-enabled';
    public const MESSAGE_TYPE_MFA_DISABLED = 'mfa-disabled';
    public const MESSAGE_TYPE_METHOD_VERIFY = 'method-verify';
    public const MESSAGE_TYPE_METHOD_REMINDER = 'method-reminder';
    public const MESSAGE_TYPE_METHOD_PURGED = 'method-purged';
    public const MESSAGE_TYPE_MFA_MANAGER = 'mfa-manager';
    public const MESSAGE_TYPE_MFA_MANAGER_HELP = 'mfa-manager-help';
    public const MESSAGE_TYPE_PASSWORD_EXPIRING = 'password-expiring';
    public const MESSAGE_TYPE_PASSWORD_EXPIRED = 'password-expired';
    public const MESSAGE_TYPE_PASSWORD_PWNED = "password-pwned";

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'sent_utc' => Yii::t('app', 'Sent (UTC)'),
        ]);
    }

    public static function getMessageTypes()
    {
        return [
            self::MESSAGE_TYPE_INVITE,
            self::MESSAGE_TYPE_MFA_RATE_LIMIT,
            self::MESSAGE_TYPE_PASSWORD_CHANGED,
            self::MESSAGE_TYPE_WELCOME,
            self::MESSAGE_TYPE_GET_BACKUP_CODES,
            self::MESSAGE_TYPE_REFRESH_BACKUP_CODES,
            self::MESSAGE_TYPE_LOST_SECURITY_KEY,
            self::MESSAGE_TYPE_MFA_OPTION_ADDED,
            self::MESSAGE_TYPE_MFA_OPTION_REMOVED,
            self::MESSAGE_TYPE_MFA_ENABLED,
            self::MESSAGE_TYPE_MFA_DISABLED,
            self::MESSAGE_TYPE_METHOD_VERIFY,
            self::MESSAGE_TYPE_METHOD_REMINDER,
            self::MESSAGE_TYPE_METHOD_PURGED,
            self::MESSAGE_TYPE_MFA_MANAGER,
            self::MESSAGE_TYPE_MFA_MANAGER_HELP,
            self::MESSAGE_TYPE_PASSWORD_EXPIRING,
            self::MESSAGE_TYPE_PASSWORD_EXPIRED,
            self::MESSAGE_TYPE_PASSWORD_PWNED,
        ];
    }

    public static function logMessage(string $messageType, $userId)
    {
        $emailLog = new EmailLog([
            'user_id' => $userId,
            'message_type' => $messageType,
        ]);

        if (!$emailLog->save()) {
            $errorMessage = sprintf(
                'Failed to log %s email to User %s: %s',
                var_export($messageType, true),
                var_export($userId, true),
                \json_encode($emailLog->getFirstErrors(), JSON_PRETTY_PRINT)
            );
            \Yii::warning($errorMessage);
            throw new Exception($errorMessage, 1502398588);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge([
            [
                'message_type',
                'required',
            ],
            [
                'message_type',
                'in',
                'range' => self::getMessageTypes(),
                'strict' => true,
            ],
            [
                'sent_utc',
                'default',
                'value' => MySqlDateTime::now(),
            ],
        ], parent::rules());
    }
}
