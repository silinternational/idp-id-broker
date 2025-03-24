<?php

use yii\db\Migration;
use common\models\Mfa;

/**
 * Class m211204_142200_add_mfa_type_webauthn
 */
class m211204_142200_add_mfa_type_webauthn extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // rename existing security key labels to include U2F to help with support later
        $this->update(
            '{{mfa}}',
            ['label' => 'Security Key (U2F)'],
            ['label' => 'Security Key']
        );

        // add webauthn mfa type
        $this->alterColumn(
            '{{mfa}}',
            'type',
            "enum('totp','u2f','backupcode','manager','webauthn') not null"
        );

        // Change all u2f mfa records to webauthn
        $this->update(
            '{{mfa}}',
            ['type' => 'webauthn'],
            ['type' => 'u2f']
        );

        // remove u2f mfa type
        $this->alterColumn(
            '{{mfa}}',
            'type',
            "enum('totp','backupcode','manager','webauthn') not null"
        );

        // add column to store webauthn key handle, will be needed in future
        $this->addColumn(
            '{{mfa}}',
            'key_handle_hash',
            'varchar(255) null'
        );

        // set initial key_handle_hash as 'u2f' to recognize as legacy
        $this->update(
            '{{mfa}}',
            ['key_handle_hash' => 'u2f'],
            ['type' => 'webauthn']
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m211204_142200_add_mfa_type_webauthn cannot be reverted.\n";
    }
}
