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
    const MESSAGE_TYPE_INVITE = 'invite';
    const MESSAGE_TYPE_MFA_RATE_LIMIT = 'mfa-rate-limit';
    const MESSAGE_TYPE_PASSWORD_CHANGED = 'password-changed';
    const MESSAGE_TYPE_WELCOME = 'welcome';
    const MESSAGE_TYPE_GET_BACKUP_CODES = 'get-backup-codes';
    const MESSAGE_TYPE_GET_NEW_BACKUP_CODES = 'get-new-backup-codes';
    const MESSAGE_TYPE_LOST_SECURITY_KEY = 'lost-security-key';
    const MESSAGE_TYPE_MFA_REQUIRED = 'mfa-required';
    const MESSAGE_TYPE_MFA_OPTION_ADDED = 'mfa-option-added';
    const MESSAGE_TYPE_MFA_OPTION_REMOVED = 'mfa-option-removed';
    const MESSAGE_TYPE_MFA_OPTION_ENABLED = 'mfa-option-enabled';
    const MESSAGE_TYPE_MFA_OPTION_DISABLED = 'mfa-option-disabled';

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
            self::MESSAGE_TYPE_GET_NEW_BACKUP_CODES,
            self::MESSAGE_TYPE_LOST_SECURITY_KEY,
            self::MESSAGE_TYPE_MFA_REQUIRED,
            self::MESSAGE_TYPE_MFA_OPTION_ADDED,
            self::MESSAGE_TYPE_MFA_OPTION_REMOVED,
            self::MESSAGE_TYPE_MFA_OPTION_ENABLED,
            self::MESSAGE_TYPE_MFA_OPTION_DISABLED,
        ];
    }
    
    public static function logMessage(string $messageType, $userId)
    {
        $emailLog = new EmailLog([
            'user_id' => $userId,
            'message_type' => $messageType,
        ]);
        
        if ( ! $emailLog->save()) {
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
