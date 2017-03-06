<?php

namespace common\models;

use common\helpers\Utils;
use yii\helpers\ArrayHelper;

class PasswordHistory extends PasswordHistoryBase
{
    public function __construct($userId, $passwordHash, array $config = [])
    {
        $this->user_id = $userId;
        $this->password_hash = $passwordHash;

        parent::__construct($config);
    }

    public function rules(): array
    {
        return ArrayHelper::merge([
            [
                'created_utc', 'default', 'value' => gmdate(Utils::DT_FMT),
            ],
        ], parent::rules());
    }
}
