<?php

namespace common\models;

use common\helpers\Utils;
use Exception;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class User extends UserBase
{
    const SCENARIO_SAVE            = 'save';
    const SCENARIO_UPDATE_PASSWORD = 'update_password';
    const SCENARIO_AUTHENTICATE    = 'authenticate';

    public $password;

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
                'password', 'string',
            ],
            [
                'password',
                $this->validatePassword(),
                'on' => self::SCENARIO_AUTHENTICATE,
            ],
            [
                ['last_synced_utc', 'last_changed_utc'],
                'default', 'value' => Utils::now(),
            ],
            [
                ['employee_id', 'first_name', 'last_name', 'display_name', 'username', 'email'],
                'string',
            ],
        ], parent::rules());
    }

    private function validatePassword(): \Closure
    {
        return function ($attributeName) {
            if (! password_verify($this->$attributeName, $this->password_hash)) {
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
                'value' => Utils::now(),
                'skipUpdateOnClean' => true, // only update the column if the model is dirty
            ],
            'updateTracker' => [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'last_synced_utc',
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'last_synced_utc',
                ],
                'value' => Utils::now(),
                'skipUpdateOnClean' => false, // always update the column regardless of dirtiness
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

    public function save($runValidation = true, $attributeNames = null)
    {
        if ($this->scenario === self::SCENARIO_UPDATE_PASSWORD) {
            return $this->savePassword($runValidation, $attributeNames);
        }

        return parent::save($runValidation, $attributeNames);
    }

    private function savePassword($runValidation = true, $attributeNames = null): bool
    {
        $transaction = ActiveRecord::getDb()->beginTransaction();

        try {
            if ($this->hasPasswordAlready()) {
                if (! $this->saveHistory()) {
                    return false;
                }
            }

            $this->password_hash = password_hash($this->password, PASSWORD_DEFAULT);

            if (! parent::save($runValidation, $attributeNames)) {
                $transaction->rollBack();

                return false;
            }

            $transaction->commit();

            return true;
        } catch (Exception $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    private function hasPasswordAlready(): bool
    {
        return ! empty($this->password_hash);
    }

    private function saveHistory(): bool
    {
        $history = new PasswordHistory();

        $history->user_id = $this->id;
        $history->password_hash = $this->password_hash;

        if (! $history->save()) {
            $this->addErrors($history->errors);

            return false;
        }

        return true;
    }
}
