<?php

use yii\db\Migration;

/**
 * Class m171116_214501_add_password_changed_email_log_type
 */
class m171116_214501_add_password_changed_email_log_type extends Migration
{
    public function safeUp()
    {
        // NOTE: I'm leaving the `'welcome'` entry here in case existing
        // database records are using it, so that we don't lose that
        // information.
        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed') NULL"
        );
    }

    public function safeDown()
    {
        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit') NULL"
        );
    }
}
