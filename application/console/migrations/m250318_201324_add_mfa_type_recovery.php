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

        //Adding in Recovery MFA emails
        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes','lost-security-key','mfa-option-added','mfa-option-removed','mfa-enabled','mfa-disabled','method-verify','mfa-manager','mfa-manager-help','method-reminder','method-purged','password-expiring','password-expired','password-pwned','ext-group-sync-errors','abandoned-users', 'mfa-recovery', 'mfa-recovery-help') null"
        );

        //Updating all manager email records to recovery emails
        $this->update("{{email_log}}", ["message_type" => "mfa-recovery"], ["message_type" => "mfa-manager"]);

        $this->update("{{email_log}}", ["message_type" => "mfa-recovery-help"], ["message_type" => "mfa-manager-help"]);

        //Drop mfa-manager and mfa-manager-help
        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes','lost-security-key','mfa-option-added','mfa-option-removed','mfa-enabled','mfa-disabled','method-verify','method-reminder','method-purged','password-expiring','password-expired','password-pwned','ext-group-sync-errors','abandoned-users', 'mfa-recovery', 'mfa-recovery-help') null"
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete("{{mfa}}", ["type" => "recovery"]);

        // remove recovery mfa type
        $this->alterColumn(
            '{{mfa}}',
            'type',
            "enum('totp','u2f','backupcode','manager','webauthn') not null"
        );

        $this->update("{{email_log}}", ["message_type" => "mfa-manager"], ["message_type" => "mfa-recovery"]);

        $this->update("{{email_log}}", ["message_type" => "mfa-manager-help"], ["message_type" => "mfa-recovery-help"]);


        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes','lost-security-key','mfa-option-added','mfa-option-removed','mfa-enabled','mfa-disabled','method-verify','mfa-manager','mfa-manager-help','method-reminder','method-purged','password-expiring','password-expired','password-pwned','ext-group-sync-errors','abandoned-users') null"
        );

    }
}
