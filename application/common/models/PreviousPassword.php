<?php

namespace common\models;

use yii\helpers\ArrayHelper;
use common\helpers\Utils;

class PreviousPassword extends PreviousPasswordBase
{
    public function rules()
    {
        return ArrayHelper::merge(
            [
                [
                    ['password_hash', 'created_utc'], 'trim'
                ],
[
                    'created_utc', 'default', 'value' => date(Utils::DT_FMT)
                ],
            ],
            parent::rules()
        );
    }
}
