<?php

use yii\db\Migration;

class m170203_210542_create_initial_tables extends Migration
{
    public function up()
    {
        $this->createUserTable();
        $this->createPreviousPasswordTable();

    }

    public function down()
    {
        $this->dropPreviousPasswordTable();
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
                'password_hash' => 'varchar(255) null',
                'active' => "enum('yes','no') not null",
                'locked' => "enum('no','yes') not null",
                'blocked_until_utc' => 'datetime null',
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

    private function createPreviousPasswordTable() 
    {
        $this->createTable(
            '{{previous_password}}',
            [
                'id' => 'pk',
                'user_id' => 'int(11) not null',
                'password_hash' => 'varchar(255) null',
                'created_utc' => 'datetime not null',
            ],
            "ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        $this->createIndex('idx_password_hash','{{previous_password}}','password_hash',false);
        
        $this->addForeignKey('fk_previous_password_user_id','{{previous_password}}','user_id','{{user}}','id','CASCADE','NO ACTION');
    }

    private function dropPreviousPasswordTable() 
    {
        $this->dropForeignKey('fk_previous_password_user_id','{{previous_password}}');

        $this->dropTable('{{previous_password}}');        
    }
}
