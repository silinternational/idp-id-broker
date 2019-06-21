<?php

namespace common\models;

use Closure;
use common\components\Emailer;
use common\helpers\MySqlDateTime;
use common\helpers\Utils;
use Exception;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\db\conditions\LikeCondition;
use yii\db\conditions\OrCondition;
use yii\helpers\ArrayHelper;

class User extends UserBase
{
    const SCENARIO_NEW_USER        = 'new_user';
    const SCENARIO_UPDATE_USER     = 'update_user';
    const SCENARIO_UPDATE_PASSWORD = 'update_password';
    const SCENARIO_AUTHENTICATE    = 'authenticate';
    const SCENARIO_INVITE          = 'invite';

    /** @var string */
    public $password;

    /** @var NagState */
    protected $nagState = null;

    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (array_key_exists('personal_email', $changedAttributes) && $this->personal_email !== $this->email) {
            $this->updateRecoveryMethods($insert, $changedAttributes['personal_email']);
        }

        $this->sendAppropriateMessages($insert, $changedAttributes);
    }
    
    public function beforeDelete()
    {
        if (! parent::beforeDelete()) {
            return false;
        }

        /*
         * First "disconnect" the user's current password and mark user as inactive to
         * prevent any emails being sent to the user.
         */
        $this->current_password_id = null;
        $this->active = 'no';
        if (! $this->save(false, ['current_password_id', 'active'])) {
            \Yii::error([
                'action' => 'unset current_password_id before deleting user',
                'status' => 'error',
                'error' => $this->getFirstErrors(),
                'user id' => $this->id,
            ]);
            return false;
        }
        
        // Next, delete dependent records:
        
        /* @var $passwordsOfUser Password[] */
        $passwordsOfUser = Password::findAll(['user_id' => $this->id]);
        foreach ($passwordsOfUser as $password) {
            if (! $password->delete()) {
                \Yii::error([
                    'action' => 'delete password record before deleting user',
                    'status' => 'error',
                    'error' => $password->getFirstErrors(),
                    'password id' => $password->id,
                    'user id' => $this->id,
                ]);
                return false;
            }
        }
        
        foreach ($this->mfas as $mfa) {
            if (! $mfa->delete()) {
                \Yii::error([
                    'action' => 'delete mfa record before deleting user',
                    'status' => 'error',
                    'error' => $mfa->getFirstErrors(),
                    'mfa_id' => $mfa->id,
                    'user_id' => $this->id,
                ]);
                return false;
            }
        }

        foreach ($this->methods as $method) {
            if (! $method->delete()) {
                \Yii::error([
                    'action' => 'delete method record before deleting user',
                    'status' => 'error',
                    'error' => $method->getFirstErrors(),
                    'mfa_id' => $method->id,
                    'user_id' => $this->id,
                ]);
                return false;
            }
        }

        foreach ($this->invites as $invite) {
            if (! $invite->delete()) {
                \Yii::error([
                    'action' => 'delete invite record before deleting user',
                    'status' => 'error',
                    'error' => $invite->getFirstErrors(),
                    'invite_id' => $invite->id,
                    'user_id' => $this->id,
                ]);
                return false;
            }
        }

        /*
         * Delete email logs last in case other deletions trigger new emails
         */
        foreach ($this->emailLogs as $emailLog) {
            if (! $emailLog->delete()) {
                \Yii::error([
                    'action' => 'delete email log record before deleting user',
                    'status' => 'error',
                    'error' => $emailLog->getFirstErrors(),
                    'email log id' => $emailLog->id,
                    'user_id' => $emailLog->user_id,
                ]);
                return false;
            }
        }

        return true;
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
            'manager_email',
            'require_mfa',
            'nag_for_mfa_after',
            'nag_for_method_after',
            'review_profile_after',
            'personal_email',
            'hide',
            'groups',
            'expires_on',
        ];

        $scenarios[self::SCENARIO_UPDATE_USER] = [
            'first_name',
            'last_name',
            'display_name',
            'username',
            'email',
            'active',
            'locked',
            'manager_email',
            'require_mfa',
            'personal_email',
            'hide',
            'groups',
        ];

        $scenarios[self::SCENARIO_UPDATE_PASSWORD] = ['password'];

        $scenarios[self::SCENARIO_AUTHENTICATE] = ['username', 'password', '!active', '!locked'];

        $scenarios[self::SCENARIO_INVITE] = ['!active', '!locked'];

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
                'nag_for_mfa_after',
                'default',
                'value' => MySqlDateTime::relative(\Yii::$app->params['mfaAddInterval']),
            ],
            [
                'nag_for_method_after',
                'default',
                'value' => MySqlDateTime::relative(\Yii::$app->params['methodAddInterval']),
            ],
            [
                'review_profile_after',
                'default',
                'value' => MySqlDateTime::relative(\Yii::$app->params['profileReviewInterval']),
            ],
            [
                ['active', 'locked', 'require_mfa', 'hide'], 'in', 'range' => ['yes', 'no'],
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
                'on' => [self::SCENARIO_AUTHENTICATE, self::SCENARIO_INVITE],
            ],
            [
                'locked', 'compare', 'compareValue' => 'no',
                'on' => [self::SCENARIO_AUTHENTICATE, self::SCENARIO_INVITE],
            ],
            [
                ['manager_email', 'personal_email'], 'email',
            ],
            [
                ['last_synced_utc', 'last_changed_utc'],
                'default', 'value' => MySqlDateTime::now(),
            ],
            [
                'expires_on',
                'default',
                'value' => $this->getExpiresOnInitialValue(),
            ],
            [
                'email', 'required', 'when' => function ($model) {
                    return $model->personal_email === null;
                }
            ],
            [
                'employee_id',
                'match',
                'pattern' => '/^\w+$/',
                'message' => 'invalid character(s) in {attribute} {value}',
                'on' => self::SCENARIO_NEW_USER,
            ],
        ], parent::rules());
    }

    private function validatePassword(): Closure
    {
        return function ($attributeName) {
            $currentPassword = $this->currentPassword ?? new Password();
            if (! password_verify($this->password, $currentPassword->hash)) {
                $this->addError($attributeName, 'Incorrect password.');
            }
        };
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
     * @throws Exception
     */
    public function getAttributesForEmail()
    {
        $attrs = [
            'employeeId' => $this->employee_id,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'displayName' => $this->getDisplayName(),
            'username' => $this->username,
            'email' => $this->getEmailAddress(),
            'active' => $this->active,
            'locked' => $this->locked,
            'lastChangedUtc' => MySqlDateTime::formatDateForHumans($this->last_changed_utc),
            'lastSyncedUtc' => MySqlDateTime::formatDateForHumans($this->last_synced_utc),
            'lastLoginUtc' => MySqlDateTime::formatDateForHumans($this->last_login_utc),
            'passwordExpiresUtc' => null, // Entry needed even if null.
            'isMfaEnabled' => count($this->mfas) > 0 ? true : false,
            'mfaOptions' => $this->getVerifiedMfaOptions(),
            'numRemainingCodes' => $this->countMfaBackupCodes(),
            'managerEmail' => $this->manager_email,
            'hasRecoveryMethods' => count($this->getVerifiedMethodOptions()) > 0 ? true : false,
        ];
        if ($this->currentPassword !== null) {
            $attrs['passwordExpiresUtc'] = MySqlDateTime::formatDateForHumans($this->currentPassword->getExpiresOn());
        }
        
        return $attrs;
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
                $gracePeriodEnds = strtotime($this->currentPassword->getGracePeriodEndsOn());

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
            'display_name' => function (self $model) {
                return $model->getDisplayName();
            },
            'username',
            'email' => function (self $model) {
                return $model->getEmailAddress();
            },
            'active',
            'locked',
            'last_login_utc' => function (self $model) {
                return $model->last_login_utc === null ? null : Utils::getIso8601($model->last_login_utc);
            },
            'manager_email',
            'personal_email' => function (self $model) {
                $maskParam = Yii::$app->request->queryParams['mask'] ?? 'no';
                if ($maskParam === 'no') {
                    return $model->personal_email;
                } else {
                    return Utils::maskEmail($model->personal_email);
                }
            },
            'hide',
            'member' => function (self $model) {
                if (empty($model->groups)) {
                    return [];
                } else {
                    return explode(',', $model->groups);
                }
            },
            'mfa' => function (self $model) {
                return $model->getMfaFields();
            },
            'method' => function (self $model) {
                return $model->getMethodFields();
            },
            'profile_review' => function (self $model) {
                return $model->getNagState() == NagState::NAG_PROFILE_REVIEW ? 'yes' : 'no';
            }
        ];

        if ($this->current_password_id !== null) {
            $fields['password'] = function () {
                return $this->currentPassword;
            };
        }

        return $fields;
    }

    /**
     * Get a display name for the user (either their display_name value, if
     * set, or a combination of their first and last names).
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->display_name ?? "$this->first_name $this->last_name";
    }

    /**
     * Based on current time and presence of MFA and Method options,
     * determine which "nag" to present to the user.
     *
     */
    public function getNagState()
    {
        if ($this->nagState === null) {
            $this->nagState = new NagState(
                $this->nag_for_mfa_after,
                $this->nag_for_method_after,
                $this->review_profile_after,
                count($this->getVerifiedMfaOptions()),
                count($this->getVerifiedMethodOptions())
            );
        }

        return $this->nagState->getState();
    }


    /**
     * @return array MFA related properties
     */
    public function getMfaFields()
    {
        return [
            'prompt'  => $this->isPromptForMfa() ? 'yes' : 'no',
            'add'     => $this->getNagState() == NagState::NAG_ADD_MFA ? 'yes' : 'no',
            'options' => $this->getVerifiedMfaOptions(),
        ];
    }

    /**
     * @return Mfa[]
     */
    public function getVerifiedMfaOptions()
    {
        $mfas = [];
        foreach ($this->mfas as $mfaOption) {
            if ($mfaOption->verified === 1) {
                if ($this->scenario == self::SCENARIO_AUTHENTICATE || $mfaOption->type !== Mfa::TYPE_MANAGER) {
                    $mfaOption->scenario = $this->scenario;
                    $mfas[] = $mfaOption;
                }
            }
        }
        return $mfas;
    }

    /**
     * @return Method[]
     */
    public function getVerifiedMethodOptions()
    {
        return array_filter($this->methods, function ($method) {
            return $method->verified === 1;
        });
    }

    /**
     * @return Method[]
     */
    public function getUnverifiedMethods()
    {
        return array_filter($this->methods, function ($method) {
            return $method->verified === 0;
        });
    }

    /**
     * Return method-related properties to include in /user responses
     *
     * @return array method-related properties
     * @throws \Exception
     */
    public function getMethodFields()
    {
        $maskParam = Yii::$app->request->queryParams['mask'] ?? 'no';

        /*
         * Provide method data when a profile review is requested OR
         * if a `mask=yes` query parameter has been given.
         */
        $shouldProvideMethodOptions =
            ($this->getNagState() === NagState::NAG_PROFILE_REVIEW
            && $this->scenario == self::SCENARIO_AUTHENTICATE)
            || $maskParam === 'yes';
        $methods = $shouldProvideMethodOptions ? $this->methods : [];

        if ($maskParam === 'yes') {
            foreach ($methods as $key => $method) {
                $methods[$key]->value = $method->getMaskedValue();
            }
        }

        return [
            'add' => $this->getNagState() == NagState::NAG_ADD_METHOD ? 'yes' : 'no',
            'options' => $methods,
        ];
    }

    /*
     * @return bool
     */
    public function hasMfaBackupCodes()
    {
        foreach ($this->getVerifiedMfaOptions() as $mfaOption) {
            if ($mfaOption->type == Mfa::TYPE_BACKUPCODE) {
                return true;
            }
        }
        return false;
    }

    /*
     * @return int the count of a user's Mfa backup codes
     */
    public function countMfaBackupCodes()
    {
        foreach ($this->getVerifiedMfaOptions() as $mfaOption) {
            if ($mfaOption->type == Mfa::TYPE_BACKUPCODE) {
                return count($mfaOption->mfaBackupcodes);
            }
        }
        return 0;
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        if ($this->scenario === self::SCENARIO_UPDATE_PASSWORD) {
            return $this->updatePassword();
        }

        return parent::save($runValidation, $attributeNames);
    }

    /**
     * @param bool $isNewUser
     * @param array $changedAttributes
     * @throws Exception
     */
    protected function sendAppropriateMessages($isNewUser, $changedAttributes)
    {
        /* @var $emailer Emailer */
        $emailer = \Yii::$app->emailer;
        
        if ($emailer->shouldSendInviteMessageTo($this, $isNewUser)) {
            $invite = Invite::findOrCreate($this->id);
            $data = ['inviteCode' => $invite->getCode()];
            /*
             * If both `personal_email` and `this_email` are valid, then this is a normal
             * invite scenario. Otherwise, there's no need to include the 'cc' because the
             * `personal_email` will be used for the 'to' address.
             */
            if ($this->personal_email && $this->email) {
                $data['ccAddress'] = $this->personal_email;
            }
            $emailer->sendMessageTo(
                EmailLog::MESSAGE_TYPE_INVITE,
                $this,
                $data,
                \Yii::$app->params['inviteEmailDelaySeconds']
            );
        }
        
        if ($emailer->shouldSendPasswordChangedMessageTo($this, $changedAttributes)) {
            $emailer->sendMessageTo(EmailLog::MESSAGE_TYPE_PASSWORD_CHANGED, $this);
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

    /*
     * @return integer Count of active users with a password
     */
    public static function countUsersWithPassword()
    {
        $users = User::find()->where(['active' => 'yes'])
            ->andWhere(['not', ['current_password_id' => null]]);

        return $users->count();
    }

    /*
     * @return integer Count of active users with require_mfa = 'yes'
     */
    public static function countUsersWithRequireMfa()
    {
        $users = User::find()->where([
            'active' => 'yes',
            'require_mfa' => 'yes',
        ]);
        return $users->count();
    }

    /**
     * @param string|null $mfaType
     * @return ActiveQuery of active Users with a (certain type of) verified Mfa option
     */
    public static function getQueryOfUsersWithMfa($mfaType = null)
    {
        $criteria = ['verified' => 1];
        if ($mfaType !== null) {
            $criteria['type'] = $mfaType;
        }

        $mfas = Mfa::find()->select('user_id')
                           ->groupBy('user_id')
                           ->where($criteria);

        $usersQuery = User::find()->where([
            'active' => 'yes',
            'id' => $mfas
        ]);

        return $usersQuery;
    }

    /**
     * If there are no active users, returns 0.
     * Otherwise, returns the total number of verified Mfa records that are associated with an active user
     *   divided by the total number of active users that have a verified Mfa.
     * @return float|int
     */
    public static function getAverageNumberOfMfasPerUserWithMfas()
    {
        $userCount = self::getQueryOfUsersWithMfa()->count();

        $mfaCount = Mfa::find()->joinWith('user')
            ->where(['verified' => 1])
            ->andWhere(['user.active' => 'yes'])->count();

        if ($userCount == 0) {
            return 0;
        }

        return $mfaCount / $userCount;
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
                case 'search':
                    $query->andWhere(new OrCondition([
                        new LikeCondition('employee_id', 'LIKE', $value),
                        new LikeCondition('first_name', 'LIKE', $value),
                        new LikeCondition('last_name', 'LIKE', $value),
                        new LikeCondition('display_name', 'LIKE', $value),
                        new LikeCondition('username', 'LIKE', $value),
                        new LikeCondition('email', 'LIKE', $value),
                        new LikeCondition('personal_email', 'LIKE', $value),
                    ]));
                    break;
                case 'fields':
                case 'mask':
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

    /**
     * @return bool
     */
    public function isPromptForMfa(): bool
    {
        if ($this->scenario == self::SCENARIO_AUTHENTICATE) {
            if ($this->require_mfa === 'yes' || count($this->getVerifiedMfaOptions()) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Update the date field that corresponds to the current nag state
     */
    public function updateProfileReviewDates()
    {
        switch ($this->getNagState()) {
            case NagState::NAG_ADD_MFA:
                $this->nag_for_mfa_after = MySqlDateTime::relative(\Yii::$app->params['mfaAddInterval']);
                break;
            case NagState::NAG_ADD_METHOD:
                $this->nag_for_method_after = MySqlDateTime::relative(\Yii::$app->params['methodAddInterval']);
                break;
            case NagState::NAG_PROFILE_REVIEW:
                $this->review_profile_after = MySqlDateTime::relative(\Yii::$app->params['profileReviewInterval']);
                break;
        }
    }

    /**
     * Add personal email to recovery methods table. On insert ($insert == true) the personal
     * email address is added to the list of recovery methods.
     * @param bool $insert
     * @throws \yii\web\ConflictHttpException
     * @throws \yii\web\ServerErrorHttpException
     */
    public function updateRecoveryMethods(bool $insert)
    {
        if ($this->personal_email === null || $insert == false) {
            return;
        }

        \Yii::warning([
            'action' => 'adding personal email',
            'status' => 'notice',
            'employee_id' => $this->employee_id,
            'email' => $this->personal_email,
        ]);

        Method::findOrCreate($this->id, $this->personal_email, MySqlDateTime::now());
    }

    /**
     * @inheritDoc
     */
    public function beforeSave($insert)
    {
        if ($insert == false && $this->personal_email !== $this->getOldAttribute('personal_email')) {
            $this->review_profile_after = MySqlDateTime::relative('-1 day');
        }

        if ($this->email === '') {
            $this->email = null;
        }

        if ($this->getOldAttribute('email') !== null && $this->email === null) {
            $this->addError('email', 'email cannot be removed');
            return false;
        }

        if (! empty($this->email)) {
            $this->expires_on = null;
        }

        return parent::beforeSave($insert);
    }

    /**
     * @return string:null
     */
    public function getExpiresOnInitialValue()
    {
        return MySqlDateTime::relative(\Yii::$app->params['contingentUserDuration']);
    }

    /**
     * @return string
     */
    public function getEmailAddress(): string
    {
        return $this->email ?? $this->personal_email ?? '';
    }

    /**
     * @inheritDoc
     */
    public function afterFind()
    {
        if ($this->expires_on !== null && MySqlDateTime::isBefore($this->expires_on, time())) {
            $this->active = 'no';

            Yii::warning([
                'event' => 'onAfterFind',
                'status' => 'user is expired',
                'employeeId' => $this->employee_id,
                'scenario' => $this->scenario,
                'expires_on' => $this->expires_on
            ], 'application');
        }

        parent::afterFind();
    }

    public function assessPassword(string $newPassword): bool
    {
        $password = new Password();

        $password->user_id = $this->id;
        $password->password = $newPassword;

        if (! $password->validate()) {
            $this->addErrors($password->getErrors());
            return false;
        }

        return true;
    }

    /**
     * Remove all manager codes for this user
     * @throws \Exception
     */
    public function removeManagerCodes()
    {
        $mfa = Mfa::findOne(['user_id' => $this->id, 'type' => Mfa::TYPE_MANAGER]);
        if ($mfa === null) {
            return;
        }

        foreach ($mfa->mfaBackupcodes as $code) {
            if ($code->delete() === false) {
                \Yii::error([
                    'action' => 'remove all manager codes',
                    'status' => 'error',
                    'error' => $code->getFirstErrors(),
                ]);
                throw new \Exception("Unable to delete manager code", 1556810506);
            }
        }

        if ($mfa->delete() === false) {
            \Yii::error([
                'action' => 'remove manager mfa',
                'status' => 'error',
                'error' => $mfa->getFirstErrors(),
            ]);
            throw new \Exception("Unable to delete manager mfa", 1556810507);
        }
    }

    /**
     * Extend grace period if password is past or nearly past the grace period. Intended to
     * be used in a situation where removal of the last MFA option has caused an immediate expiration
     * of the user's password.
     */
    public function extendGracePeriodIfNeeded()
    {
        if (count($this->getVerifiedMfaOptions()) > 0) {
            return;
        }

        if ($this->currentPassword === null) {
            return;
        }

        $gracePeriodEnds = strtotime($this->currentPassword->getGracePeriodEndsOn());

        $nowPlusExtension = strtotime(\Yii::$app->params['passwordGracePeriodExtension']);

        /*
         * If grace period has ended or will end in the near future, bump it out to allow
         * time for the user to change their password.
         */
        if ($gracePeriodEnds < $nowPlusExtension) {
            $this->currentPassword->extendGracePeriod();

            \Yii::warning([
                'action' => 'extend grace period',
                'status' => 'success',
                'username' => $this->username,
                'grace_period_ends_on' => $this->currentPassword->grace_period_ends_on,
            ]);
        }
    }

    /**
     * Delete all user records that are inactive and have not been updated recently.
     */
    public static function deleteInactiveUsers()
    {
        $enabled = \Yii::$app->params['inactiveUserDeletionEnable'];

        if ($enabled !== true) {
            \Yii::warning([
                'action' => 'delete inactive users',
                'status' => 'disabled',
            ]);
            return;
        }

        \Yii::warning([
            'action' => 'delete inactive users',
            'status' => 'starting',
        ]);

        /*
         * Replace '+' with '-' so all env parameters can be defined consistently as '+n unit'
         */
        $inactiveUserPeriod = '-' . ltrim(\Yii::$app->params['inactiveUserPeriod'], '+');

        /**
         * @var string $removeBefore   All records that have not been updated since before this date
         * should be deleted. Calculated relative to now (time of execution).
         */
        $removeBefore = MySqlDateTime::relative($inactiveUserPeriod);
        $users = self::find()
            ->andWhere(['<', 'last_changed_utc', $removeBefore])
            ->andWhere(['active' => 'no'])
            ->all();

        $numDeleted = 0;
        foreach ($users as $user) {
            try {
                if ($user->delete() !== false) {
                    $numDeleted += 1;
                }
            } catch (\Exception $e) {
                \Yii::error([
                    'action' => 'delete inactive users',
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'uuid' => $user->uuid,
                ]);
            }
        }

        \Yii::warning([
            'action' => 'delete inactive users',
            'status' => 'complete',
            'count' => $numDeleted,
        ]);
    }
}
