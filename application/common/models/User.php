<?php

namespace common\models;

use yii\helpers\ArrayHelper;
use common\helpers\Utils;

class User extends UserBase
{
    public function rules()
    {
        return ArrayHelper::merge(
            [
                [
                    ['employee_id', 'first_name', 'last_name', 'username', 'email', 'active', 'locked', 'last_changed_utc', 'last_synced_utc'], 'trim'
                ],
                [
                    'uuid', 'default', 'value' => Utils::genUuid(),
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
                    ['last_changed_utc', 'last_synced_utc'], 'default', 'value' => date(Utils::DT_FMT)
                ],
                [
                    'email', 'email',
                ]
            ],
            parent::rules()
        );
    }
}
