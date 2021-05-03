<?php

use yii\db\Migration;

/**
 * Class m210503_094330_add_deactivated_utc_to_user
 */
class m210503_094330_add_deactivated_utc_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{user}}', 'deactivated_utc', 'datetime null');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{user}}', 'deactivated_utc');
    }

}
