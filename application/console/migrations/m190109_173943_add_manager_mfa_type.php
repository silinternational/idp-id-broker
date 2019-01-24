<?php

use yii\db\Migration;

/**
 * Class m190109_173943_add_manager_mfa_type
 */
class m190109_173943_add_manager_mfa_type extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            '{{mfa}}',
            'type',
            "enum('totp','u2f','backupcode','manager') not null"
        );

        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes'," .
            "'lost-security-key','mfa-option-added','mfa-option-removed'," .
            "'mfa-enabled','mfa-disabled','method-verify','mfa-manager') NULL"
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
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes'," .
            "'lost-security-key','mfa-option-added','mfa-option-removed'," .
            "'mfa-enabled','mfa-disabled','method-verify') NULL"
        );

        $this->alterColumn(
            '{{mfa}}',
            'type',
            "enum('totp','u2f','backupcode') not null"
        );
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190109_173943_add_manager_mfa_type cannot be reverted.\n";

        return false;
    }
    */
}
