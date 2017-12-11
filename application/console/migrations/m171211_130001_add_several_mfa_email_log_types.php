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
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','get-new-backup-codes'," .
                "'lost-security-key','mfa-required','mfa-option-added','mfa-option-removed'," .
                "'mfa-option-enabled','mfa-option-disabled') NULL"
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
