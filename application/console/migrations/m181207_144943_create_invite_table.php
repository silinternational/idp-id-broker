<?php

use yii\db\Migration;

/**
 * Handles the creation of table `invite`.
 */
class m181207_144943_create_invite_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(
            'invite',
            [
                'id' => 'pk',
                'user_id' => 'int(11) not null',
                'uuid' => 'char(36) CHARACTER SET ascii COLLATE ascii_general_ci not null',
                'created_utc' => 'datetime not null',
                'expires_on' => 'date not null',
            ],
            "ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        $this->addForeignKey(
            'fk_invite_user_id',
            '{{invite}}',
            'user_id',
            '{{user}}',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('invite');
    }
}
