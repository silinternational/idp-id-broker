<?php

namespace common\models;

use common\helpers\Utils;
use yii\helpers\ArrayHelper;

class PasswordHistory extends PasswordHistoryBase
{
    //TODO: build in rule, can't match one of last 10 passwords.
    public function rules(): array
    {
        return ArrayHelper::merge([
            [
                'created_utc', 'default', 'value' => Utils::now(),
            ],
        ], parent::rules());
    }
}
