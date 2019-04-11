<?php

use yii\db\Migration;

/**
 * Handles adding expiration to table `user`.
 */
class m190314_014900_add_expiration_column_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{user}}', 'expires_on', 'date null');
        $this->alterColumn('{{user}}', 'email', 'varchar(255)');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{user}}', 'email', 'varchar(255) not null');
        $this->dropColumn('{{user}}', 'expires_on');
    }
}
