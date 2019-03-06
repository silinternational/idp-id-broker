<?php

use yii\db\Migration;

/**
 * Class m190306_171024_remove_spouse_email_column_from_user_table
 */
class m190306_171024_remove_spouse_email_column_from_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('{{user}}', 'spouse_email');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->addColumn('{{user}}', 'spouse_email', 'varchar(255) null');
    }
}
