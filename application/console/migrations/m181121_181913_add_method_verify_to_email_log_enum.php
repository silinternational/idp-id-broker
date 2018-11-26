<?php

use yii\db\Migration;

/**
 * Class m181121_181913_add_method_verify_to_email_log_enum
 */
class m181121_181913_add_method_verify_to_email_log_enum extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes'," .
            "'lost-security-key','mfa-option-added','mfa-option-removed'," .
            "'mfa-enabled','mfa-disabled','method-verify') NULL"
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes'," .
            "'lost-security-key','mfa-option-added','mfa-option-removed'," .
            "'mfa-enabled','mfa-disabled') NULL"
        );
    }
}
