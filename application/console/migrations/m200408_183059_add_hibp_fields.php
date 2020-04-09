<?php

use yii\db\Migration;

/**
 * Class m200408_183059_add_hibp_fields
 */
class m200408_183059_add_hibp_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{password}}', 'check_hibp_after', 'date not null default "0000-00-00"');
        $this->addColumn('{{password}}', 'hibp_is_pwned', "enum('no','yes') not null default 'no'");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{password}}', 'check_hibp_after');
        $this->dropColumn('{{password}}', 'hibp_is_pwned');
    }

}
