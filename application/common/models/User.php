<?php

namespace common\models;

use Closure;
use common\helpers\MySqlDateTime;
use Exception;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use Yii;

class User extends UserBase
{
    const SCENARIO_NEW_USER        = 'new_user';
    const SCENARIO_UPDATE_USER     = 'update_user';
    const SCENARIO_UPDATE_PASSWORD = 'update_password';
    const SCENARIO_AUTHENTICATE    = 'authenticate';

    public $password;
    
    protected function attemptPasswordMigration()
    {
        if (empty($this->username)) {
            Yii::warning('No username given for checking against ldap.');
            return;
        }
        
        if (empty($this->password)) {
            Yii::warning('No password given for checking against ldap.');
            return;
        }
        
        $user = User::findByUsername($this->username);
        if ($user === null) {
            Yii::warning(sprintf(
                'No user found with that username (%s) when trying to check '
                . 'password against ldap.',
                var_export($this->username, true)
            ));
            return;
        }
        
        if (Yii::$app->ldap->isPasswordCorrectForUser($this->username, $this->password)) {
            
            /* Try to save the password, but let the user proceed even if
             * we can't (since we know the password is correct).  */
            $user->scenario = User::SCENARIO_UPDATE_PASSWORD;
            $user->password = $this->password;
            $savedPassword = $user->savePassword();
            if ( ! $savedPassword) {
                Yii::warning(sprintf(
                    'Confirmed given password for %s against LDAP, but failed '
                    . 'to save password hash to database: %s',
                    var_export($this->username, true),
                    json_encode($this->getFirstErrors())
                ));
            } else {
                $this->refresh();
            }
        }
    }
    
    /**
     * @param string $username
     * @return User|null
     */
    public static function findByUsername(string $username)
    {
        return User::findOne(['username' => $username]);
    }

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();

        $scenarios[self::SCENARIO_DEFAULT] = null;

        $scenarios[self::SCENARIO_NEW_USER] = [
            'employee_id',
            'first_name',
            'last_name',
            'display_name',
            'username',
            'email',
            'active',
            'locked',
        ];

        $scenarios[self::SCENARIO_UPDATE_USER] = [
            'first_name',
            'last_name',
            'display_name',
            'username',
            'email',
            'active',
            'locked',
        ];

        $scenarios[self::SCENARIO_UPDATE_PASSWORD] = ['password'];

        $scenarios[self::SCENARIO_AUTHENTICATE] = ['username', 'password', '!active', '!locked'];

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
                // special note:  we always want to hash a password first as a best practice
                //  against timing attacks.  Therefore, this rule should be run before most
                //  other rules.  https://en.wikipedia.org/wiki/Timing_attack
                'password',
                $this->validatePassword(),
                'on' => self::SCENARIO_AUTHENTICATE,
            ],
            [
                'active', 'compare', 'compareValue' => 'yes',
                'on' => self::SCENARIO_AUTHENTICATE,
            ],
            [
                'locked', 'compare', 'compareValue' => 'no',
                'on' => self::SCENARIO_AUTHENTICATE,
            ],
            [
                ['last_synced_utc', 'last_changed_utc'],
                'default', 'value' => MySqlDateTime::now(),
            ],
        ], parent::rules());
    }

    private function validatePassword(): Closure
    {
        return function ($attributeName) {
            
            if ( ! $this->hasPasswordAlready()) {
                $this->attemptPasswordMigration();
            }
            
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
                'value' => MySqlDateTime::now(),
                'skipUpdateOnClean' => true, // only update the value if something has changed
            ],
            'syncTracker' => [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'last_synced_utc',
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'last_synced_utc',
                ],
                'value' => $this->updateOnSync(),
                'skipUpdateOnClean' => false, // always consider updating the value whether something has changed or not.
            ],
        ];
    }

    private function updateOnSync(): Closure
    {
        return function () {
            return $this->isSync($this->scenario) ? MySqlDateTime::now()
                                                  : $this->last_synced_utc;
        };
    }

    private function isSync($scenario): bool
    {
        return in_array($scenario, [self::SCENARIO_NEW_USER, self::SCENARIO_UPDATE_USER]);
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
            'display_name' => function ($model) {
                return $model->display_name ?? $model->first_name . ' ' . $model->last_name;
            },
            'username',
            'email',
            'active',
            'locked',
        ];
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        if ($this->scenario === self::SCENARIO_UPDATE_PASSWORD) {
            return $this->savePassword();
        }

        return parent::save($runValidation, $attributeNames);
    }

    private function savePassword(): bool
    {
        $transaction = ActiveRecord::getDb()->beginTransaction();

        try {
            if ($this->hasPasswordAlready()) {
                if (! $this->saveHistory()) {
                    return false;
                }
            }

            $this->password_hash = password_hash($this->password, PASSWORD_DEFAULT);

            if (! parent::save()) {
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
        $history->password = $this->password;
        $history->password_hash = $this->password_hash;

        if (! $history->save()) {
            $this->addErrors($history->errors);

            return false;
        }

        return true;
    }
}
