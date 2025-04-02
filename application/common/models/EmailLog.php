<?php

namespace common\models;

use common\helpers\MySqlDateTime;
use ReflectionClass;
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
    public const MESSAGE_TYPE_ABANDONED_USERS = 'abandoned-users';
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
    public const MESSAGE_TYPE_MFA_RECOVERY = 'mfa-recovery';
    public const MESSAGE_TYPE_MFA_RECOVERY_HELP = 'mfa-recovery-help';
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
            'non_user_address' => Yii::t('app', 'Non-User Address'),
        ]);
    }

    public static function getMessageTypes(): array
    {
        $reflectionClass = new ReflectionClass(__CLASS__);
        $messageTypes = [];
        foreach ($reflectionClass->getConstants() as $name => $value) {
            if (str_starts_with($name, 'MESSAGE_TYPE_')) {
                $messageTypes[] = $value;
            }
        }
        return $messageTypes;
    }

    public static function logMessageToUser(string $messageType, $userId)
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
            throw new \Exception($errorMessage, 1502398588);
        }
    }

    public static function logMessageToNonUser(string $messageType, string $emailAddress)
    {
        $emailLog = new EmailLog([
            'non_user_address' => $emailAddress,
            'message_type' => $messageType,
        ]);

        if (!$emailLog->save()) {
            $errorMessage = sprintf(
                'Failed to log %s email to %s: %s',
                var_export($messageType, true),
                var_export($emailAddress, true),
                \json_encode($emailLog->getFirstErrors(), JSON_PRETTY_PRINT)
            );
            \Yii::warning($errorMessage);
            throw new \Exception($errorMessage, 1730909932);
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
            [
                'non_user_address',
                'emptyIfUserIsSet',
            ],
            [
                'non_user_address',
                'requiredIfUserIsEmpty',
                'skipOnEmpty' => false,
            ],
        ], parent::rules());
    }

    /**
     * Validate an attribute to ensure it is empty (unset) if `user_id` is set.
     *
     * @param string $attribute Attribute name to be validated
     * @return bool
     */
    public function emptyIfUserIsSet($attribute)
    {
        if (!empty($this->user_id) && !empty($this[$attribute])) {
            $this->addError(
                $attribute,
                sprintf(
                    'Only a user_id or a %s may be set, not both',
                    $attribute
                )
            );
        }
    }

    /**
     * Validate an attribute to ensure it is not empty if `user_id` is not set.
     *
     * @param string $attribute Attribute name to be validated
     * @return bool
     */
    public function requiredIfUserIsEmpty($attribute)
    {
        if (empty($this->user_id) && empty($this[$attribute])) {
            $this->addError(
                $attribute,
                sprintf(
                    'Either a user_id or a %s must be set',
                    $attribute
                )
            );
        }
    }
}
