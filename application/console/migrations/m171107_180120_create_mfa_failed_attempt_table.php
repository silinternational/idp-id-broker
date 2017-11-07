<?php

use yii\db\Migration;

/**
 * Handles the creation of table `mfa_failed_attempt`.
 */
class m171107_180120_create_mfa_failed_attempt_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('mfa_failed_attempt', [
            'id' => $this->primaryKey(),
            'mfa_id' => 'int(11) NOT NULL',
            'at_utc' => 'datetime NOT NULL',
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci');

        $this->addForeignKey(
            'fk_mfa_failed_attempt_mfa_id',
            '{{mfa_failed_attempt}}',
            'mfa_id',
            '{{mfa}}',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropForeignKey(
            'fk_mfa_failed_attempt_mfa_id',
            '{{mfa_failed_attempt}}'
        );
        $this->dropTable('mfa_failed_attempt');
    }
}
