<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Class m241029_202830_refactor_email_log_to_use_address
 */
class m241029_202830_refactor_email_log_to_use_address extends Migration
{
    public function safeUp()
    {
        // Add the `to_address` column.
        $this->addColumn(
            '{{email_log}}',
            'to_address',
            $this->string()->notNull()->defaultValue('')
        );

        // Populate the `to_address` column for existing records.
        $this->execute(
        'UPDATE `email_log`, `user`
             SET `email_log`.`to_address` = `user`.`email`
             WHERE `email_log`.`user_id` = `user`.`id`'
        );

        // Add a foreign key on `to_address`.
        $this->addForeignKey(
            'fk_to_address',
            '{{email_log}}',
            'to_address',
            '{{user}}',
            'email',
            'NO ACTION',
            'NO ACTION'
        );

        // Remove the `user_id` column.
        $this->dropForeignKey('fk_user_id', '{{email_log}}');
        $this->dropColumn('{{email_log}}', 'user_id');
    }

    public function safeDown()
    {
        echo "m241029_202830_refactor_email_log_to_use_address cannot be reverted.\n";
        return false;
    }
}
