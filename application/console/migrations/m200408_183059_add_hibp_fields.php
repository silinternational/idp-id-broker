<?php

use yii\db\Migration;

/**
 * Class m200408_183059_add_hibp_fields
 */
class m200408_183059_add_hibp_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{password}}', 'check_hibp_after', 'date not null default "0000-00-00"');
        $this->addColumn('{{password}}', 'hibp_is_pwned', "enum('no','yes') not null default 'no'");
        $this->alterColumn('{{email_log}}', 'message_type', "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes','lost-security-key','mfa-option-added','mfa-option-removed','mfa-enabled','mfa-disabled','method-verify','mfa-manager','mfa-manager-help','method-reminder','method-purged','password-expiring','password-expired','password-pwned') null");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{email_log}}', 'message_type', "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes','lost-security-key','mfa-option-added','mfa-option-removed','mfa-enabled','mfa-disabled','method-verify','mfa-manager','mfa-manager-help','method-reminder','method-purged','password-expiring','password-expired') null");
        $this->dropColumn('{{password}}', 'check_hibp_after');
        $this->dropColumn('{{password}}', 'hibp_is_pwned');
    }
}
