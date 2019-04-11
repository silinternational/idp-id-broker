<?php

use yii\db\Migration;

/**
 * Class m190214_103811_add_personal_email_to_user_table
 */
class m190214_103811_add_personal_email_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{user}}', 'personal_email', 'varchar(255) null');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{user}}', 'personal_email');
    }
}
