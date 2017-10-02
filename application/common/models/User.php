<?php

namespace common\models;

use Closure;
use common\components\Emailer;
use common\helpers\MySqlDateTime;
use common\ldap\Ldap;
use Exception;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\data\ActiveDataProvider;
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

    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        
        $this->sendAppropriateMessages($insert, $changedAttributes);
    }
    
    public function setLdap(Ldap $ldap)
    {
        $this->ldap = $ldap;
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

    public function rules(): array
    {
        return ArrayHelper::merge([
            [
                'uuid', 'default', 'value' => Uuid::uuid4()->toString()
            ],
            [
                'active', 'default', 'value' => 'yes', 'on' => self::SCENARIO_NEW_USER
            ],
            [
                'locked', 'default', 'value' => 'no', 'on' => self::SCENARIO_NEW_USER
            ],
            [
                'require_mfa', 'default', 'value' => 'no', 'on' => self::SCENARIO_NEW_USER
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
                // special note:  As a best practice against timing attacks this rule should be run
                // before most other rules.  https://en.wikipedia.org/wiki/Timing_attack
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

            if ($this->current_password_id === null) {
                $this->attemptPasswordMigration();
            }

            $currentPassword = $this->currentPassword ?? new Password();
            if (! password_verify($this->password, $currentPassword->hash)) {
                $this->addError($attributeName, 'Incorrect password.');
            }
        };
    }

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
                $savedPassword = $user->updatePassword();
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

    public static function findByEmail(string $email)
    {
        return User::findOne(['email' => $email]);
    }

    /**
     * Get the list of attributes about this User that are safe to include in
     * an email to them.
     *
     * NOTE: The resulting array uses camelCased attribute names for keys, so
     *       the User's "employee_id" will have a key of "employeeId".
     *
     * @return array
     */
    public function getAttributesForEmail()
    {
        return [
            'employeeId' => $this->employee_id,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'displayName' => $this->display_name,
            'username' => $this->username,
            'email' => $this->email,
            'active' => $this->active,
            'locked' => $this->locked,
            'lastChangedUtc' => $this->last_changed_utc,
            'lastSyncedUtc' => $this->last_synced_utc,
        ];
    }
    
    public function hasReceivedMessage(string $messageType)
    {
        return $this->getEmailLogs()->where([
            'message_type' => $messageType,
        ])->exists();
    }
    
    private function validateExpiration(): Closure
    {
        return function ($attributeName) {
            if ($this->currentPassword !== null) {
                $gracePeriodEnds = strtotime("{$this->currentPassword->grace_period_ends_on} 23:59:59 UTC");

                $now = time();

                if ($now > $gracePeriodEnds) {
                    $this->addError($attributeName, 'Expired password.');
                }
            } else {
                $this->addError($attributeName, 'Nonexistent password.');
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
            'prompt_for_mfa' => function ($model) {
                if ($model->require_mfa == 'yes' || count($model->mfas) > 0) {
                    return 'yes';
                }
                return 'no';
            },
            'mfa_options' => function ($model) {
                return $model->mfas;
            }
        ];

        if ($this->current_password_id !== null) {
            $fields['password'] = function () {
                return $this->currentPassword;
            };
        }

        return $fields;
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        if ($this->scenario === self::SCENARIO_UPDATE_PASSWORD) {
            return $this->updatePassword();
        }

        return parent::save($runValidation, $attributeNames);
    }
    
    protected function sendAppropriateMessages($isNewUser, $changedAttributes)
    {
        /* @var $emailer Emailer */
        $emailer = \Yii::$app->emailer;
        
        if ($emailer->shouldSendInviteMessageTo($this, $isNewUser)) {
            $emailer->sendMessageTo(EmailLog::MESSAGE_TYPE_INVITE, $this);
        }
        
        if ($emailer->shouldSendWelcomeMessageTo($this, $changedAttributes)) {
            $emailer->sendMessageTo(EmailLog::MESSAGE_TYPE_WELCOME, $this);
        }
    }

    private function updatePassword(): bool
    {
        $transaction = ActiveRecord::getDb()->beginTransaction();

        try {

            if (! $this->savePassword()) {
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

    private function savePassword()
    {
        $password = new Password();

        $password->user_id = $this->id;
        $password->password = $this->password;

        if (! $password->save()) {
            $this->addErrors($password->errors);

            return false;
        }

        $this->current_password_id = $password->id;

        return true;
    }

    /**
     * @param $criteria array Criteria to be used for filtering users based upon their password
     *                        expiration, e.g., ['grace_period_ends_on' => '2018-07-16', 'expires_on' => '2018-06-17'].
     * @return array Users matching given criteria
     */
    public static function getExpiringUsers($criteria): array
    {
        if (empty($criteria)) {
            return [];
        }

        $users = User::find()->joinWith('currentPassword')
                             ->where(['active' => 'yes']);

        foreach ($criteria as $name => $value) {
            switch ($name) {
                case 'expires_on':
                case 'grace_period_ends_on':
                    $users->andWhere(["password.$name" => $value]);
                    break;
                default:
                    // if no criteria names match, this will ensure an empty result is returned
                    $users->where('0=1');
            }
        }

        return $users->all();
    }

    public static function getUsersWithFirstPasswords($createdOn): array
    {
        //  find the earliest password for each user, if it matches the provided date, then return
        //  that user's info:
        //        SELECT *
        //        FROM user
        //        WHERE id in (
        //          SELECT user_id
        //	        FROM password
        //	        GROUP BY user_id
        //	        HAVING DATE(MIN(created_utc)) = "2017-06-07"
        //        )
        $oldestPasswords = Password::find()->select('user_id')
                                           ->groupBy('user_id')
                                           ->having(['=', "DATE(MIN(created_utc))", $createdOn]);

        $users = User::find()->where([
                                'active' => 'yes',
                                'id' => $oldestPasswords
                               ]);

        return $users->all();
    }

    public static function search($params): ActiveDataProvider
    {
        $query = User::find();

        foreach ($params as $name => $value) {
            switch ($name) {
                case 'username':
                case 'email':
                    $query->andWhere([$name => $value]);
                    break;
                case 'fields':
                    break;
                default:
                    // if no criteria names match, this will ensure an empty result is returned
                    $query->where('0=1');
            }
        }

        /* NOTE: Return a DataProvider here (rather than an array of Models) so
         *       that the Serializer can limit the fields returned if a 'fields'
         *       query string parameter is present requesting only certain
         *       fields.  */
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
        ]);
    }

    public function attributeLabels()
    {
        $labels = parent::attributeLabels();

        $labels['last_changed_utc'] = Yii::t('app', 'Last Changed (UTC)');
        $labels['last_synced_utc'] = Yii::t('app', 'Last Synced (UTC)');

        return $labels;
    }
}
