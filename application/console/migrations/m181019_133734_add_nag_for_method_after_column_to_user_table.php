<?php

use yii\db\Migration;

/**
 * Handles adding nag_for_method_after to table `user`.
 */
class m181019_133734_add_nag_for_method_after_column_to_user_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->addColumn('user', 'nag_for_method_after', 'date not null');
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropColumn('user', 'nag_for_method_after');
    }
}
