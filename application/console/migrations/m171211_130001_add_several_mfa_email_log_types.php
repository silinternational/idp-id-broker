<?php

use yii\db\Migration;

/**
 * Class m171211_130001_add_several_mfa_email_log_types
 */
class m171211_130001_add_several_mfa_email_log_types extends Migration
{
    public function safeUp()
    {
        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes'," .
                "'lost-security-key','mfa-option-added','mfa-option-removed'," .
                "'mfa-enabled','mfa-disabled') NULL"
        );
    }

    public function safeDown()
    {
        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed') NULL"
        );
    }
}
