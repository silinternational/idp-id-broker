<?php

namespace common\models;

use common\helpers\Utils;
use yii\helpers\ArrayHelper;

class PasswordHistory extends PasswordHistoryBase
{
    public function __construct(string $userId, string $passwordHash, array $config = [])
    {
        $this->user_id = $userId;
        $this->password_hash = $passwordHash;

        parent::__construct($config);
    }

    public function rules(): array
    {
        return ArrayHelper::merge([
            [
                'created_utc', 'default', 'value' => Utils::now(),
            ],
        ], parent::rules());
    }
}
