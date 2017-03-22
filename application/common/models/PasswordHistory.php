<?php

namespace common\models;

use common\helpers\MySqlDateTime;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class PasswordHistory extends PasswordHistoryBase
{
    public $password;

    public function rules(): array
    {
        return ArrayHelper::merge([
            [
                'created_utc', 'default', 'value' => MySqlDateTime::now(),
            ],
            [
                'password', 'required',
            ],
            [
                'password', 'string',
            ],
            [
                'password_hash',
                $this->validateReuseLimit(),
            ],
        ], parent::rules());
    }

    private function validateReuseLimit(): \Closure
    {
        return function ($attributeName) {
            if ($this->hasAlreadyBeenUsedWithinLimit()) {
                $this->addError($attributeName, 'May not be reused yet.');
            }
        };
    }

    private function hasAlreadyBeenUsedWithinLimit(): bool
    {
        /** @var PasswordHistory[] $previousPasswords */
        $previousPasswords = $this->user->getPasswordHistories()
                                        ->orderBy(['id' => SORT_DESC])
                                        ->limit(10)
                                        ->all();

        foreach ($previousPasswords as $previousPassword) {
            if (password_verify($this->password, $previousPassword->password_hash)) {
                return true;
            }
        }

        return false;
    }

    public function behaviors(): array
    {
        return [
            'createdTracker' => [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_utc',
                ],
                'value' => MySqlDateTime::now()
            ],
        ];
    }

    public function attributeLabels()
    {
        $labels = parent::attributeLabels();

        $labels['created_utc'] = Yii::t('app', 'Created (UTC)');

        return $labels;
    }
}
