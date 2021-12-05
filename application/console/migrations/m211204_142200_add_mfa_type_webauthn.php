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
        $this->alterColumn(
            '{{mfa}}',
            'type',
            "enum('totp','u2f','backupcode','manager','webauthn') not null"
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