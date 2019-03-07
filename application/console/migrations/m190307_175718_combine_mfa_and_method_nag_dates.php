<?php

use yii\db\Migration;

/**
 * Class m190307_175718_combine_mfa_and_method_nag_dates
 */
class m190307_175718_combine_mfa_and_method_nag_dates extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('{{user}}', 'nag_for_method_after');
        $this->dropColumn('{{user}}', 'nag_for_mfa_after');
        $this->addColumn('{{user}}', 'review_profile_after', 'date not null');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{user}}', 'review_profile_after');
        $this->addColumn('{{user}}', 'nag_for_mfa_after', 'date not null');
        $this->addColumn('{{user}}', 'nag_for_method_after', 'date not null');
    }
}
