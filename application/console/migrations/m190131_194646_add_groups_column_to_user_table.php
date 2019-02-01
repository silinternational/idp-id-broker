<?php

use yii\db\Migration;

/**
 * Handles adding groups to table `user`.
 */
class m190131_194646_add_groups_column_to_user_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{user}}', '{{groups}}', 'varchar(255) null');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{user}}', '{{groups}}');
    }
}
