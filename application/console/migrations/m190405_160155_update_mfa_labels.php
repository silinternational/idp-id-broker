<?php

use yii\db\Migration;
use common\models\Mfa;

/**
 * Class m190405_160155_update_mfa_labels
 */
class m190405_160155_update_mfa_labels extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        foreach (Mfa::getTypes() as $type => $label) {
            $this->update(
                '{{mfa}}',
                ['label' => $label],
                ['type' => $type]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190405_160155_update_mfa_labels cannot be reverted.\n";
    }
}
