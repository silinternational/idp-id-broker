<?php

use yii\db\Migration;

/**
 * Handles adding do_not_disclose to table `user`.
 */
class m181128_213015_add_do_not_disclose_column_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'do_not_disclose', "enum('no','yes') not null");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('user', 'do_not_disclose');
    }
}
