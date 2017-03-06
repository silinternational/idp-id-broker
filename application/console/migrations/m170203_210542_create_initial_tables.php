<?php

use yii\db\Migration;

class m170203_210542_create_initial_tables extends Migration
{
    public function up()
    {
        $this->createUserTable();
        $this->createPasswordHistoryTable();

    }

    public function down()
    {
        $this->dropPasswordHistoryTable();
        $this->dropUserTable();
    }

    private function createUserTable()
    {
        $this->createTable(
            '{{user}}',
            [
                'id' => 'pk',
                'employee_id' => 'varchar(255) not null',
                'first_name' => 'varchar(255) not null',
                'last_name' => 'varchar(255) not null',
                'display_name' => 'varchar(255) not null',
                'username' => 'varchar(255) not null',
                'email' => 'varchar(255) not null',
                'password_hash' => 'varchar(255) null',
                'active' => "enum('yes','no') not null",
                'locked' => "enum('no','yes') not null",
                'last_changed_utc' => 'datetime not null',
                'last_synced_utc' => 'datetime not null',
            ],
            "ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        $this->createIndex('uq_user_employee_id','{{user}}','employee_id',true);
        $this->createIndex('uq_user_username','{{user}}','username',true);
        $this->createIndex('uq_user_email','{{user}}','email',true);
    }

    private function dropUserTable()
    {
        $this->dropTable('{{user}}');
    }

    private function createPasswordHistoryTable()
    {
        $this->createTable(
            '{{password_history}}',
            [
                'id' => 'pk',
                'user_id' => 'int(11) not null',
                'password_hash' => 'varchar(255) not null',
                'created_utc' => 'datetime not null',
            ],
            "ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        $this->createIndex('idx_password_hash','{{password_history}}','password_hash',false);

        $this->addForeignKey('fk_password_history_user_id','{{password_history}}','user_id','{{user}}','id','CASCADE','NO ACTION');
    }

    private function dropPasswordHistoryTable()
    {
        $this->dropForeignKey('fk_password_history_user_id','{{password_history}}');

        $this->dropTable('{{password_history}}');
    }
}
