<?php

use yii\db\Migration;

class m171107_212911_add_mfa_rate_limit_email_log_type extends Migration
{
    public function safeUp()
    {
        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit') NULL"
        );
    }

    public function safeDown()
    {
        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome') NULL"
        );
    }
}
