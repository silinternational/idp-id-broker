<?php

use yii\db\Migration;

/**
 * Class m241106_155829_enable_logging_non_user_emails
 */
class m241106_155829_enable_logging_non_user_emails extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{email_log}}',
            'non_user_address',
            $this->string()->null()
        );
        $this->alterColumn(
            '{{email_log}}',
            'user_id',
            $this->integer()->null()
        );
    }

    public function safeDown()
    {
        $this->delete(
            '{{email_log}}',
            [
                'user_id' => null,
            ]
        );
        $this->alterColumn(
            '{{email_log}}',
            'user_id',
            $this->integer()->notNull()
        );
        $this->dropColumn(
            '{{email_log}}',
            'non_user_address'
        );
    }
}
