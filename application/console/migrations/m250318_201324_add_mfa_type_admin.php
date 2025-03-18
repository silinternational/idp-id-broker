<?php

use yii\db\Migration;
use common\models\Mfa;

/**
 * Class m211204_142200_add_mfa_type_webauthn
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

        // add column to store webauthn admin recovery email
        $this->addColumn(
            '{{mfa}}',
            'admin_email',
            'varchar(255) null'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // add webauthn mfa type
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
    }
}
