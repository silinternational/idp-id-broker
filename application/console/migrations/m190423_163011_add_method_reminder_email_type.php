<?php

use yii\db\Migration;

/**
 * Class m190423_163011_add_method_reminder_email_type
 */
class m190423_163011_add_method_reminder_email_type extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes'," .
            "'refresh-backup-codes','lost-security-key','mfa-option-added','mfa-option-removed'," .
            "'mfa-enabled','mfa-disabled','method-verify','mfa-manager','mfa-manager-help'," .
            "'method-reminder','method-purged','password-expiring','password-expired') NULL"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes'," .
            "'refresh-backup-codes','lost-security-key','mfa-option-added','mfa-option-removed'," .
            "'mfa-enabled','mfa-disabled','method-verify','mfa-manager','mfa-manager-help') NULL"
        );
    }
}
