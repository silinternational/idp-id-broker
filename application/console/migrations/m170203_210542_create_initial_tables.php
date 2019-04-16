<?php

use yii\db\Migration;

class m170203_210542_create_initial_tables extends Migration
{
    public function safeUp()
    {
        $this->createUserTable();
        $this->createPasswordTable();

        $this->createForeignKeys();
    }

    public function safeDown()
    {
        $this->dropForeignKeys();

        $this->dropPasswordTable();
        $this->dropUserTable();
    }

    private function createUserTable()
    {
        $this->createTable(
            '{{user}}',
            [
                'id' => 'pk',
                'uuid' => 'varchar(64) not null',
                'employee_id' => 'varchar(255) not null',
                'first_name' => 'varchar(255) not null',
                'last_name' => 'varchar(255) not null',
                'display_name' => 'varchar(255) null',
                'username' => 'varchar(255) not null',
                'email' => 'varchar(255) not null',
                'current_password_id' => 'int(11) null',
                'active' => "enum('yes','no') not null",
                'locked' => "enum('no','yes') not null",
                'last_changed_utc' => 'datetime not null',
                'last_synced_utc' => 'datetime not null',
            ],
            "ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        $this->createIndex('uq_user_employee_id', '{{user}}', 'employee_id', true);
        $this->createIndex('uq_user_username', '{{user}}', 'username', true);
        $this->createIndex('uq_user_email', '{{user}}', 'email', true);
    }

    private function dropUserTable()
    {
        $this->dropTable('{{user}}');
    }

    private function createPasswordTable()
    {
        $this->createTable(
            '{{password}}',
            [
                'id' => 'pk',
                'user_id' => 'int(11) not null',
                'hash' => 'varchar(255) not null',
                'created_utc' => 'datetime not null',
                'expires_on' => 'date not null',
                'grace_period_ends_on' => 'date not null',
            ],
            "ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
    }

    private function dropPasswordTable()
    {
        $this->dropTable('{{password}}');
    }

    private function createForeignKeys()
    {
        $this->addForeignKey('fk_user_to_current_password', '{{user}}', 'current_password_id', '{{password}}', 'id', 'CASCADE');
    }

    private function dropForeignKeys()
    {
        $this->dropForeignKey('fk_user_to_current_password', '{{user}}');
    }
}
