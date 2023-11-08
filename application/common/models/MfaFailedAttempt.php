<?php

namespace common\models;

use common\helpers\MySqlDateTime;
use Yii;
use yii\helpers\ArrayHelper;

class MfaFailedAttempt extends MfaFailedAttemptBase
{
    public const RECENT_FAILURE_LIMIT = 5;

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'mfa_id' => Yii::t('app', 'MFA ID'),
            'at_utc' => Yii::t('app', 'At (UTC)'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge([
            [
                'at_utc',
                'default',
                'value' => MySqlDateTime::now(),
            ],
        ], parent::rules());
    }
}
