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
        $this->renameColumn('{{user}}', 'nag_for_mfa_after', 'review_profile_after');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->renameColumn('{{user}}', 'review_profile_after', 'nag_for_mfa_after');
        $this->addColumn('{{user}}', 'nag_for_method_after', 'date not null');
    }
}
