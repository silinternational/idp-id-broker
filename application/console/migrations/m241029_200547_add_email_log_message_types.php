<?php

use yii\db\Migration;

/**
 * Class m241029_200547_add_email_log_message_types
 */
class m241029_200547_add_email_log_message_types extends Migration
{
    public function safeUp()
    {
        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes','lost-security-key','mfa-option-added','mfa-option-removed','mfa-enabled','mfa-disabled','method-verify','mfa-manager','mfa-manager-help','method-reminder','method-purged','password-expiring','password-expired','password-pwned','ext-group-sync-errors','abandoned-users') null"
        );
    }

    public function safeDown()
    {
        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes','lost-security-key','mfa-option-added','mfa-option-removed','mfa-enabled','mfa-disabled','method-verify','mfa-manager','mfa-manager-help','method-reminder','method-purged','password-expiring','password-expired','password-pwned') null"
        );
    }
}
