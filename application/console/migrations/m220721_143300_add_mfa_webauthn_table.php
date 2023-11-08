<?php

use yii\db\Migration;
use common\models\Mfa;

/**
* Class m220721_143300_add_mfa_webauthn_table
*/
class m220721_143300_add_mfa_webauthn_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(
            '{{mfa_webauthn}}',
            [
                'id' => 'pk',
                'mfa_id' => 'int(11) not null',
                'key_handle_hash' => 'varchar(255) null',
                'label' => 'varchar(64) not null',
                'created_utc' => 'datetime not null',
                'last_used_utc' => 'datetime null',
            ],
            "ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        $this->addForeignKey(
            'fk_mfa_webauthn_mfa_id',
            '{{mfa_webauthn}}',
            'mfa_id',
            '{{mfa}}',
            'id',
            'NO ACTION',
            'NO ACTION'
        );

        // Copy the webauthn mfa entries over to the new mfa_webauthn table
        $this->execute("
INSERT INTO mfa_webauthn (mfa_id, label, key_handle_hash, last_used_utc, created_utc) 
SELECT id, label, key_handle_hash, last_used_utc, created_utc
FROM mfa 
WHERE 
type='webauthn'
");
        // blank out the webauthn mfa records' label and key_handle_hash values
        $this->update(
            '{{mfa}}',
            ['label' => '', 'key_handle_hash' => ''],
            ['type' => 'webauthn']
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220721_143300_add_mfa_webauthn_table cannot be reverted.\n";
    }
}
