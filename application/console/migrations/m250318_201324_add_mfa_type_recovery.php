<?php

use yii\db\Migration;
use common\models\Mfa;

/**
 * Class m250318_201324_add_mfa_type_recovery
 */
class m250318_201324_add_mfa_type_recovery extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        // add recovery mfa type
        $this->alterColumn(
            '{{mfa}}',
            'type',
            "enum('totp','u2f','backupcode','manager','webauthn', 'recovery') not null"
        );

        // add column to store recovery email
        $this->addColumn(
            '{{mfa}}',
            'recovery_email',
            'varchar(255) null'
        );

        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes','lost-security-key','mfa-option-added','mfa-option-removed','mfa-enabled','mfa-disabled','method-verify','mfa-manager','mfa-manager-help','method-reminder','method-purged','password-expiring','password-expired','password-pwned','ext-group-sync-errors','abandoned-users', 'mfa-recovery', 'mfa-recovery-help') null"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // remove recovery mfa type
        $this->alterColumn(
            '{{mfa}}',
            'type',
            "enum('totp','u2f','backupcode','manager','webauthn') not null"
        );

        // Drop column that stores recovery email
        $this->dropColumn(
            '{{mfa}}',
            'recovery_email'
        );

        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes','lost-security-key','mfa-option-added','mfa-option-removed','mfa-enabled','mfa-disabled','method-verify','mfa-manager','mfa-manager-help','method-reminder','method-purged','password-expiring','password-expired','password-pwned','ext-group-sync-errors','abandoned-users') null"
        );
    }
}
