<?php

use yii\db\Migration;

class m170928_174802_add_mfa_tables extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{user}}', 'require_mfa', "enum('no','yes')");
        $this->addColumn('{{user}}', 'nag_for_mfa_after', 'date not null');

        $this->createTable(
            '{{mfa}}',
            [
                'id' => 'pk',
                'user_id' => 'int(11) not null',
                'type' => "enum('totp','u2f','backupcode') not null",
                'external_uuid' => 'varchar(64) null',
                'label' => 'varchar(64) null',
                'verified' => 'tinyint(1) not null',
                'created_utc' => 'datetime not null',
                'last_used_utc' => 'datetime null',
            ],
            "ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        $this->addForeignKey(
            'fk_mfa_user_id',
            '{{mfa}}',
            'user_id',
            '{{user}}',
            'id',
            'NO ACTION',
            'NO ACTION'
        );

        $this->createTable(
            '{{mfa_backupcode}}',
            [
                'id' => 'pk',
                'mfa_id' => 'int(11) not null',
                'value' => 'varchar(255) not null',
                'created_utc' => 'datetime not null',
                'expires_utc' => 'datetime null',
            ],
            "ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        $this->addForeignKey(
            'fk_mfa_backupcode_mfa_id',
            '{{mfa_backupcode}}',
            'mfa_id',
            '{{mfa}}',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{user}}', 'require_mfa');
        $this->dropColumn('{{user}}', 'nag_for_mfa_after');
        $this->dropTable('{{mfa_backupcode}}');
        $this->dropTable('{{mfa}}');
    }
}
