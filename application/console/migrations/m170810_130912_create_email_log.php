<?php

use yii\db\Migration;

class m170810_130912_create_email_log extends Migration
{
    public function safeUp()
    {
        $this->createTable(
            '{{email_log}}',
            [
                'id' => 'pk',
                'user_id' => 'integer NOT NULL',
                'message_type' => "enum('invite','welcome') NULL",
                'sent_utc' => 'datetime NOT NULL',
            ],
            'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci'
        );

        $this->addForeignKey(
            'fk_user_id',
            '{{email_log}}',
            'user_id',
            '{{user}}',
            'id',
            'CASCADE', // If that `user` is deleted, DELETE this `email_log` entry too.
            'CASCADE' // If that `user.id` value changes, UPDATE this `email_log.user_id` too.
        );
        $this->createIndex(
            'idx_email_log_message_type',
            '{{email_log}}',
            'message_type'
        );
    }

    public function safeDown()
    {
        $this->dropIndex('idx_email_log_message_type', '{{email_log}}');
        $this->dropForeignKey('fk_user_id', '{{email_log}}');

        $this->dropTable('{{email_log}}');
    }
}
