<?php

use yii\db\Migration;

class m171012_150523_add_last_login_utc_to_user extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{user}}', 'last_login_utc', 'datetime null');
    }

    public function safeDown()
    {
        $this->dropColumn('{{user}}', 'last_login_utc');
    }
}
