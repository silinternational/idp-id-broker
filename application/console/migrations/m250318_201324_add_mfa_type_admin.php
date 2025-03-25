<?php

use yii\db\Migration;
use common\models\Mfa;

/**
 * Class m250318_201324_add_mfa_type_admin
 */
class m250318_201324_add_mfa_type_admin extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        // add webauthn mfa type
        $this->alterColumn(
            '{{mfa}}',
            'type',
            "enum('totp','u2f','backupcode','manager','webauthn', 'admin') not null"
        );

        // add column to store admin recovery email
        $this->addColumn(
            '{{mfa}}',
            'admin_email',
            'varchar(255) null'
        );

        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes','lost-security-key','mfa-option-added','mfa-option-removed','mfa-enabled','mfa-disabled','method-verify','mfa-manager','mfa-manager-help','method-reminder','method-purged','password-expiring','password-expired','password-pwned','ext-group-sync-errors','abandoned-users', 'mfa-admin', 'mfa-admin-help') null"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // remove admin mfa type
        $this->alterColumn(
            '{{mfa}}',
            'type',
            "enum('totp','u2f','backupcode','manager','webauthn') not null"
        );

        // Drop column that stores webauthn admin recovery email
        $this->dropColumn(
            '{{mfa}}',
            'admin_email'
        );

        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes','lost-security-key','mfa-option-added','mfa-option-removed','mfa-enabled','mfa-disabled','method-verify','mfa-manager','mfa-manager-help','method-reminder','method-purged','password-expiring','password-expired','password-pwned','ext-group-sync-errors','abandoned-users') null"
        );
    }
}
