<?php

namespace common\models;

use common\helpers\Utils;
use yii\helpers\ArrayHelper;

class PasswordHistory extends PasswordHistoryBase
{
    public $password;

    public function rules(): array
    {
        return ArrayHelper::merge([
            [
                'created_utc', 'default', 'value' => Utils::now(),
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
}
