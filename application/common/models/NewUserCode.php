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
                'expires_on', 'default', 'value' => $this->expires(),
            ],
        ], parent::rules());
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

    public function isValidCode()
    {
        $expiration = strtotime($this->expires_on);

        $now = time();
        if ($now > $expiration) {
            return false;
        }
        return true;
    }
}
