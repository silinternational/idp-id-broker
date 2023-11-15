<?php

use yii\db\Migration;

/**
 * Class m180703_134555_add_spouse_and_manager_emails
 */
class m180703_134555_add_spouse_and_manager_emails extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{user}}', 'manager_email', 'varchar(255) null');
        $this->addColumn('{{user}}', 'spouse_email', 'varchar(255) null');
    }

    public function safeDown()
    {
        $this->dropColumn('{{user}}', 'manager_email');
        $this->dropColumn('{{user}}', 'spouse_email');
    }
}
