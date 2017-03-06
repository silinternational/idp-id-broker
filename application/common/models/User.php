<?php

namespace common\models;

use common\helpers\Utils;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class User extends UserBase
{
    const SCENARIO_SAVE            = 'save';
    const SCENARIO_UPDATE_PASSWORD = 'update_password';
    const SCENARIO_AUTHENTICATE    = 'authenticate';

    public $password;

    public function savePassword(string $password)
    {
        $this->password = $password;

        $previous = $this->password_hash;

        $this->password_hash = password_hash($this->password, PASSWORD_DEFAULT);

        return $previous;
    }

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();

        $scenarios[self::SCENARIO_DEFAULT] = [
            'employee_id',
            'first_name',
            'last_name',
            'display_name',
            'username',
            'email',
            'active',
            'locked',
        ];

        $scenarios[self::SCENARIO_UPDATE_PASSWORD] = ['password'];

        $scenarios[self::SCENARIO_AUTHENTICATE] = ['username', 'password'];

        return $scenarios;
    }

    public function rules(): array
    {
        return ArrayHelper::merge([
            [
                [
                    'employee_id',
                    'first_name',
                    'last_name',
                    'display_name',
                    'username',
                    'email',
                    'active',
                    'locked',
                    'password'
                ],
                'trim'
            ],
            [
                'active', 'default', 'value' => 'yes',
            ],
            [
                'locked', 'default', 'value' => 'no',
            ],
            [
                ['active', 'locked'], 'in', 'range' => ['yes', 'no'],
            ],
            [
                'email', 'email',
            ],
            [
                'password', 'required',
                'on' => [self::SCENARIO_UPDATE_PASSWORD, self::SCENARIO_AUTHENTICATE],
            ],
            [
                'password', 'string', 'min' => 2, // 'min' is needed until https://github.com/yiisoft/yii2/issues/13701 is resolved.
            ],
            [
                'password',
                $this->validatePassword(),
                'on' => self::SCENARIO_AUTHENTICATE,
            ],
            [
                ['last_synced_utc', 'last_changed_utc'],
                'default', 'value' => gmdate(Utils::DT_FMT),
            ],
            [   // 'min' is needed on any strings until https://github.com/yiisoft/yii2/issues/13701 is resolved.
                ['employee_id', 'first_name', 'last_name', 'display_name', 'username', 'email'],
                'string', 'min' => 2
            ],
        ], parent::rules());
    }

    private function validatePassword(): \Closure
    {
        return function ($attributeName) {
            if (! password_verify($this->password, $this->password_hash)) {
                $this->addError($attributeName, 'Incorrect password.');
            }
        };
    }

    public function behaviors(): array
    {
        return [
            'changeTracker' => [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'last_changed_utc',
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'last_changed_utc',
                ],
                'value' => gmdate(Utils::DT_FMT),
            ],
            'updateTracker' => [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'last_synced_utc',
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'last_synced_utc',
                ],
                'value' => gmdate(Utils::DT_FMT),
                'skipUpdateOnClean' => false,
            ],
        ];
    }

    /**
     * @return array of fields that should be included in responses.
     */
    public function fields(): array
    {
        return [
            'employee_id',
            'first_name',
            'last_name',
            'display_name',
            'username',
            'email',
            'active',
            'locked',
            'last_changed_utc',
            'last_synced_utc',
        ];
    }
}
