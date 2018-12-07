<?php

namespace common\models;

use Closure;
use common\helpers\MySqlDateTime;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class NewUserCode extends NewUserCodeBase
{
    public function rules(): array
    {
        return ArrayHelper::merge([
            [
                'uuid', 'default', 'value' => Uuid::uuid4()->toString()
            ],
            [
                'created_utc', 'default', 'value' => MySqlDateTime::now(),
            ],
            [
                'expires_on',
                $this->validateExpiration(),
            ],
        ], parent::rules());
    }

    public function behaviors(): array
    {
        return [
            'expirationTracker' => [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'expires_on',
                ],
                'value' => $this->expires()
            ],
        ];
    }

    private function expires(): Closure
    {
        return function() {
            $lifespan = Yii::$app->params['newUserCodeLifespan'];

            return MySqlDateTime::formatDate(strtotime($lifespan, strtotime($this->created_utc)));
        };
    }

    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();

        $labels['uuid'] = Yii::t('app', 'UUID');
        $labels['created_utc'] = Yii::t('app', 'Created (UTC)');

        return $labels;
    }

    private function validateExpiration(): Closure
    {
        return function ($attributeName) {
            $expiration = strtotime($this->expires_on);

            $now = time();

            if ($now > $expiration) {
                $this->addError($attributeName, 'Expired code.');
            }
        };
    }
}
