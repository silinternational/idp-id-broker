<?php

use yii\db\Migration;

/**
 * Class m190411_201535_add_nag_dates_to_user_table
 */
class m190411_201535_add_nag_dates_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{user}}', 'nag_for_mfa_after', 'date not null');
        $this->addColumn('{{user}}', 'nag_for_method_after', 'date not null');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{user}}', 'nag_for_method_after');
        $this->dropColumn('{{user}}', 'nag_for_mfa_after');
    }
}
