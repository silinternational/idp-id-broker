<?php

use yii\db\Migration;

/**
 * Class m210112_185351_add_created_utc_to_user
 */
class m210112_185351_add_created_utc_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{user}}', 'created_utc', 'datetime null');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{user}}', 'created_utc');
    }

}
