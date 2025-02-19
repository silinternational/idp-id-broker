<?php

use yii\db\Migration;

/**
 * Handles the creation of table `email`.
 */
class m250219_000000_add_email_queue_table extends Migration
{
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTable(
            '{{email}}',
            [
                'id' => 'pk',
                'to_address' => 'varchar(255) not null',
                'cc_address' => 'varchar(255) null',
                'bcc_address' => 'varchar(255) null',
                'subject' => 'varchar(255) not null',
                'text_body' => 'text null',
                'html_body' => 'text null',
                'attempts_count' => 'tinyint not null default 0',
                'updated_at' => 'int(11) null',
                'created_at' => 'int(11) not null',
                'send_after' => 'int(11) null',
            ],
            'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci'
        );

    }

    public function safeDown()
    {
        $this->dropTable('{{email}}');
    }
}
