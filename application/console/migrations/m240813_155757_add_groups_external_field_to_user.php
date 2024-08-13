<?php

use yii\db\Migration;

/**
 * Class m240813_155757_add_groups_external_field_to_user
 */
class m240813_155757_add_groups_external_field_to_user extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{user}}',
            'groups_external',
            $this->string()->notNull()->defaultValue('')->after('groups')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{user}}', 'groups_external');
    }
}
