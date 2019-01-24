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
            'SET NULL',
            'SET NULL'
        );
        
        // For consistency's sake, also remove any other CASCADE-ing foreign
        // keys we've set up, using the models' beforeDelete() methods instead.
        $this->dropForeignKey('fk_user_id', '{{email_log}}');
        $this->alterColumn('{{email_log}}', 'user_id', 'integer NULL');
        $this->addForeignKey(
            'fk_user_id',
            '{{email_log}}',
            'user_id',
            '{{user}}',
            'id',
            'SET NULL',
            'SET NULL'
        );

        $this->dropForeignKey('fk_invite_user_id', '{{invite}}');
        $this->alterColumn('{{invite}}', 'user_id', 'integer NULL');
        $this->addForeignKey(
            'fk_invite_user_id',
            '{{invite}}',
            'user_id',
            '{{user}}',
            'id',
            'SET NULL',
            'SET NULL'
        );

        $this->dropForeignKey('fk_method_user_id', '{{method}}');
        $this->alterColumn('{{method}}', 'user_id', 'integer NULL');
        $this->addForeignKey(
            'fk_method_user_id',
            '{{method}}',
            'user_id',
            '{{user}}',
            'id',
            'SET NULL',
            'SET NULL'
        );

        $this->dropForeignKey('fk_mfa_user_id', '{{mfa}}');
        $this->alterColumn('{{mfa}}', 'user_id', 'integer NULL');
        $this->addForeignKey(
            'fk_mfa_user_id',
            '{{mfa}}',
            'user_id',
            '{{user}}',
            'id',
            'SET NULL',
            'SET NULL'
        );

        $this->dropForeignKey('fk_mfa_backupcode_mfa_id', '{{mfa_backupcode}}');
        $this->alterColumn('{{mfa_backupcode}}', 'mfa_id', 'integer NULL');
        $this->addForeignKey(
            'fk_mfa_backupcode_mfa_id',
            '{{mfa_backupcode}}',
            'mfa_id',
            '{{mfa}}',
            'id',
            'SET NULL',
            'SET NULL'
        );

        $this->dropForeignKey('fk_mfa_failed_attempt_mfa_id', '{{mfa_failed_attempt}}');
        $this->alterColumn('{{mfa_failed_attempt}}', 'mfa_id', 'integer NULL');
        $this->addForeignKey(
            'fk_mfa_failed_attempt_mfa_id',
            '{{mfa_failed_attempt}}',
            'mfa_id',
            '{{mfa}}',
            'id',
            'SET NULL',
            'SET NULL'
        );
    }
    
    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_mfa_failed_attempt_mfa_id', '{{mfa_failed_attempt}}');
        $this->alterColumn('{{mfa_failed_attempt}}', 'mfa_id', 'integer NOT NULL');
        $this->addForeignKey(
            'fk_mfa_failed_attempt_mfa_id',
            '{{mfa_failed_attempt}}',
            'mfa_id',
            '{{mfa}}',
            'id',
            'NO ACTION',
            'NO ACTION'
        );

        $this->dropForeignKey('fk_mfa_backupcode_mfa_id', '{{mfa_backupcode}}');
        $this->alterColumn('{{mfa_backupcode}}', 'mfa_id', 'integer NOT NULL');
        $this->addForeignKey(
            'fk_mfa_backupcode_mfa_id',
            '{{mfa_backupcode}}',
            'mfa_id',
            '{{mfa}}',
            'id',
            'NO ACTION',
            'NO ACTION'
        );

        $this->dropForeignKey('fk_mfa_user_id', '{{mfa}}');
        $this->alterColumn('{{mfa}}', 'user_id', 'integer NOT NULL');
        $this->addForeignKey(
            'fk_mfa_user_id',
            '{{mfa}}',
            'user_id',
            '{{user}}',
            'id',
            'NO ACTION',
            'NO ACTION'
        );

        $this->dropForeignKey('fk_method_user_id', '{{method}}');
        $this->alterColumn('{{method}}', 'user_id', 'integer NOT NULL');
        $this->addForeignKey(
            'fk_method_user_id',
            '{{method}}',
            'user_id',
            '{{user}}',
            'id',
            'NO ACTION',
            'NO ACTION'
        );

        $this->dropForeignKey('fk_invite_user_id', '{{invite}}');
        $this->alterColumn('{{invite}}', 'user_id', 'integer NOT NULL');
        $this->addForeignKey(
            'fk_invite_user_id',
            '{{invite}}',
            'user_id',
            '{{user}}',
            'id',
            'NO ACTION',
            'NO ACTION'
        );

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
