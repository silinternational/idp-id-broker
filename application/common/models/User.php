<?php

namespace common\models;

use yii\helpers\ArrayHelper;

class User extends UserBase
{
    const SCENARIO_CREATE = "create";

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[self::SCENARIO_CREATE] = [
            'employee_id',
            'first_name',
            'last_name',
            'display_name',
            'username',
            'email',
        ];

        return $scenarios;
    }

    public function rules()
    {
        return ArrayHelper::merge(
            [
                [
                    ['employee_id', 'first_name', 'last_name', 'display_name', 'username', 'email', 'active', 'locked'], 'trim'
                ],
                [
                    'active', 'default', 'value' => 'yes',
                ],
                [
                    'locked', 'default', 'value' => 'no',
                ],
                [
                    // loosen restrictions on case-sensitivity
                    ['active', 'locked'], 'match', 'pattern' => '/^yes|no$/i'
                ],
                [
                    // keep values lowercase in database
                    ['active', 'locked'], 'filter', 'filter' => function ($value) {
                        return strtolower($value);
                    }
                ],
                [
                    'email', 'email',
                ]
            ],
            parent::rules()
        );
    }

    public function fields()
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
