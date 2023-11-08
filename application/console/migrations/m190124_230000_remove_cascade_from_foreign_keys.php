<?php

use yii\db\Migration;

/**
 * Class m190124_230000_remove_cascade_from_foreign_keys
 */
class m190124_230000_remove_cascade_from_foreign_keys extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Stop the User -> Current Password foreign key from deleting the User
        // if/when the Password is deleted.
        $this->dropForeignKey('fk_user_to_current_password', '{{user}}');
        $this->addForeignKey(
            'fk_user_to_current_password',
            '{{user}}',
            'current_password_id',
            '{{password}}',
            'id',
            'NO ACTION',
            'NO ACTION'
        );

        // For consistency's sake, also remove any other CASCADE-ing foreign
        // keys we've set up, using the models' beforeDelete() methods instead.
        $this->dropForeignKey('fk_user_id', '{{email_log}}');
        $this->addForeignKey(
            'fk_user_id',
            '{{email_log}}',
            'user_id',
            '{{user}}',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_user_id', '{{email_log}}');
        $this->addForeignKey(
            'fk_user_id',
            '{{email_log}}',
            'user_id',
            '{{user}}',
            'id',
            'CASCADE', // If that `user` is deleted, DELETE this `email_log` entry too.
            'CASCADE' // If that `user.id` value changes, UPDATE this `email_log.user_id` too.
        );

        $this->dropForeignKey('fk_user_to_current_password', '{{user}}');
        $this->addForeignKey(
            'fk_user_to_current_password',
            '{{user}}',
            'current_password_id',
            '{{password}}',
            'id',
            'CASCADE' // This was the problem: deleting that password deletes this user.
        );
    }
}
