<?php

namespace common\models;

use Closure;
use common\helpers\MySqlDateTime;
use common\ldap\Ldap;
use Exception;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class User extends UserBase
{
    const SCENARIO_NEW_USER        = 'new_user';
    const SCENARIO_UPDATE_USER     = 'update_user';
    const SCENARIO_UPDATE_PASSWORD = 'update_password';
    const SCENARIO_AUTHENTICATE    = 'authenticate';

    public $password;

    /** @var Ldap */
    private $ldap;

    protected function attemptPasswordMigration()
    {
        try {
            if ($this->ldap === null) {

                // If no LDAP was provided, simply skip password migration.
                return;
            }

            if (empty($this->username)) {
                $this->addError(
                    'username',
                    'No username given for checking against ldap.'
                );
                return;
            }

            if (empty($this->password)) {
                $this->addError(
                    'password',
                    'No password given for checking against ldap.'
                );
                return;
            }

            $user = User::findByUsername($this->username);
            if ($user === null) {
                $this->addError('username', sprintf(
                    'No user found with that username (%s) when trying to check '
                    . 'password against ldap.',
                    var_export($this->username, true)
                ));
                return;
            }

            if ($this->ldap->isPasswordCorrectForUser($this->username, $this->password)) {

                /* Try to save the password, but let the user proceed even if
                 * we can't (since we know the password is correct).  */
                $user->scenario = User::SCENARIO_UPDATE_PASSWORD;
                $user->password = $this->password;
                $savedPassword = $user->savePassword();
                if ( ! $savedPassword) {

                    /**
                     * @todo If adding errors here causes a problem (because I think
                     * it will cause the `validate()` call to return false... right?)
                     * then find some other way to record/report what happened. We
                     * may be able to use Yii::warn(...), but we'll have to update
                     * the LdapContext Behat test file accordingly, since it gets
                     * the errors and reports them (to help the developer debug).
                     */
                    $this->addError('password', sprintf(
                        'Confirmed given password for %s against LDAP, but '
                        . 'failed to save password hash to database: %s',
                        var_export($this->username, true),
                        json_encode($user->getFirstErrors())
                    ));
                } else {
                    $this->refresh();
                }
            }
        } catch (Exception $e) {
            $this->addError('password', sprintf(
                'Unexpected error while attempting to migrate password: %s',
                $e->getMessage()
            ));
        }
    }

    public static function findByUsername(string $username)
    {
        return User::findOne(['username' => $username]);
    }

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();

        $scenarios[self::SCENARIO_DEFAULT] = null; // force consumers to choose a scenario

        $scenarios[self::SCENARIO_NEW_USER] = [
            '!uuid',
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

    public function setLdap(Ldap $ldap)
    {
        $this->ldap = $ldap;
    }

    public function rules(): array
    {
        return ArrayHelper::merge([
            [
                'uuid', 'default', 'value' => Uuid::uuid4()->toString()
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
                'password',
                $this->validateExpiration(),
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

    private function validateExpiration(): Closure
    {
        return function ($attributeName) {
            $now = time();
            $expiration = $this->addGracePeriod($this->getPasswordExpiration());

            if ($now > $expiration) {
                $this->addError($attributeName, 'Expired password.');
            }
        };
    }

    private function getPasswordExpiration(): string
    {
        /** @var $mostRecentPassword PasswordHistory */
        $mostRecentPassword = $this->getPasswordHistories()
                                   ->orderBy(['id' => SORT_DESC])
                                   ->one();

        return $mostRecentPassword->expires();

    }

    private function addGracePeriod(string $expiration): int
    {
        $gracePeriod = Yii::$app->params['passwordExpirationGracePeriod'];

        return strtotime($gracePeriod, strtotime($expiration));
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
                'skipUpdateOnClean' => false, // update the value whether something has changed or not.
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
        $fields = [
            'uuid',
            'employee_id',
            'first_name',
            'last_name',
            'display_name' => function ($model) {
                return $model->display_name ?? "$model->first_name $model->last_name";
            },
            'username',
            'email',
            'active',
            'locked',
        ];

        if ($this->hasPasswordAlready()) {
            $fields['password_expires_at_utc'] = function () {
                return $this->getPasswordExpiration();
            };

            $fields['password_last_changed'] = function () {
                /** @var $mostRecentPassword PasswordHistory */
                $mostRecentPassword = $this->getPasswordHistories()
                                           ->orderBy(['id' => SORT_DESC])
                                           ->one();

                return $mostRecentPassword->created_utc;
            };
        }

        return $fields;
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
            $this->password_hash = password_hash($this->password, PASSWORD_DEFAULT);

            if (! $this->saveHistory()) {
                return false;
            }

            if (! parent::save()) {
                $transaction->rollBack();

                return false;
            }

            $transaction->commit();

            return true;
        } catch (Exception $e) {
            $transaction->rollBack();

            Yii::warning("Something went wrong trying to save a new password for $this->employee_id: $e");

            throw $e;
        }
    }

    public function hasPasswordAlready(): bool
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
