<?php

namespace common\models;

use Closure;
use common\helpers\MySqlDateTime;
use common\helpers\Utils;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\ConflictHttpException;

/**
 * Class Password
 * @package common\models
 *
 * @property User $user
 */
class Password extends PasswordBase
{
    public const SCENARIO_UPDATE_METADATA = 'update_metadata';

    public const SCENARIO_REHASH = 'rehash';

    // hash algorithm passed to PHPs `password_hash` function -- if this is changed, the options
    // parameter passed to any `password_` functions may need to be changed as well
    public const HASH_ALGORITHM = PASSWORD_BCRYPT;

    public const HASH_COST = 13;

    public $password;

    public function rules(): array
    {
        return ArrayHelper::merge([
            [
                'created_utc', 'default', 'value' => MySqlDateTime::now(),
            ],
            [
                'expires_on', 'default', 'value' => MySqlDateTime::today(),
            ],
            [
                'grace_period_ends_on', 'default', 'value' => MySqlDateTime::today(),
            ],
            [
                'password', 'required', 'on' => self::SCENARIO_DEFAULT
            ],
            [
                'password', 'string',
            ],
            [
                'password', 'checkRecentlyUsed', 'on' => self::SCENARIO_DEFAULT
            ],
            [
                'hash', 'default', 'value' => function () {
                    return self::hashPassword($this->password);
                },
            ],
            [
                'hash', $this->isHashable(),
            ],
        ], parent::rules());
    }

    private function isHashable(): Closure
    {
        return function ($attributeName) {
            if ($this->hash === false) {
                $this->addError($attributeName, 'Unable to hash password.');
            }
        };
    }

    private function hasAlreadyBeenUsedTooRecently(): bool
    {
        $reuseLimit = Yii::$app->params['passwordReuseLimit'];

        /** @var Password[] $passwords */
        $passwords = Password::find()->where(['user_id' => $this->user_id])
                                     ->orderBy(['id' => SORT_DESC])
                                     ->limit($reuseLimit)
                                     ->all();

        foreach ($passwords as $password) {
            if (password_verify($this->password, $password->hash)) {
                return true;
            }
        }

        return false;
    }

    public function behaviors(): array
    {
        return [
            'createdTracker' => [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_utc',
                ],
                'value' => MySqlDateTime::now()
            ],
            'expirationTracker' => [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'expires_on',
                ],
                'value' => $this->expires()
            ],
            'gracePeriodTracker' => [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'grace_period_ends_on',
                ],
                'value' => $this->gracePeriodEnds()
            ],
        ];
    }

    private function expires(): Closure
    {
        return function () {
            $lifespan = Yii::$app->params['passwordLifespan'];

            return MySqlDateTime::formatDate(strtotime($lifespan, strtotime($this->created_utc)));
        };
    }

    private function gracePeriodEnds(): Closure
    {
        return function () {
            $gracePeriod = Yii::$app->params['passwordExpirationGracePeriod'];

            return MySqlDateTime::formatDate(strtotime($gracePeriod, strtotime($this->expires_on)));
        };
    }

    /**
     * @return array of fields that should be included in responses.
     */
    public function fields(): array
    {
        $fields = [
            'created_utc' => function ($model) {
                return Utils::getIso8601($model->created_utc);
            },
            'expires_on' => function (Password $model) {
                return $model->getExpiresOn();
            },
        ];

        return $fields;
    }

    public function attributeLabels()
    {
        $labels = parent::attributeLabels();

        $labels['created_utc'] = Yii::t('app', 'Created (UTC)');

        return $labels;
    }

    /**
     * Returns a date extended by the MFA Lifespan Extension, if applicable
     * @param string $date date in yyyy-mm-dd format
     * @return string conditionally extended and converted to ISO8601 format
     */
    protected function getMfaExtendedDate($date)
    {
        $dateIso = $date . 'T23:59:59Z';
        if ($this->user->getVerifiedMfaOptionsCount() > 0) {
            $extended = strtotime(\Yii::$app->params['passwordMfaLifespanExtension'], strtotime($dateIso));
            return Utils::getIso8601($extended);
        }
        return $dateIso;
    }

    /**
     * If password is pwned, return actual expires_on.
     * Otherwise calculate expires_on date based on if user has MFA configured
     * @return string
     */
    public function getExpiresOn()
    {
        return $this->hibp_is_pwned == 'yes' ? $this->expires_on : $this->getMfaExtendedDate($this->expires_on);
    }

    /**
     * If password is pwned, return actual grace_period_ends_on.
     * Otherwise calculate grace_period_ends_on based on if user has MFA configured
     * @return string
     */
    public function getGracePeriodEndsOn()
    {
        return $this->hibp_is_pwned == 'yes' ? $this->grace_period_ends_on : $this->getMfaExtendedDate($this->grace_period_ends_on);
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * @param $attribute
     * @throws ConflictHttpException
     */
    public function checkRecentlyUsed($attribute)
    {
        if ($this->hasAlreadyBeenUsedTooRecently()) {
            throw new ConflictHttpException('May not be reused yet', 1542395933);
        }
    }

    /**
     * Extend grace period to a point in the near future.
     */
    public function extendGracePeriod()
    {
        $newGracePeriodEnd = strtotime(\Yii::$app->params['passwordGracePeriodExtension']);

        $this->grace_period_ends_on = MySqlDateTime::formatDate($newGracePeriodEnd);

        $this->scenario = self::SCENARIO_UPDATE_METADATA;

        if (!$this->save()) {
            \Yii::error('Failed to save grace period. ' . join(', ', $this->getFirstErrors()));
        }
    }

    /**
     * @inheritDoc
     */
    public function scenarios(): array
    {
        $scenarios = parent::scenarios();

        $scenarios[self::SCENARIO_UPDATE_METADATA] = [
            'expires_on',
            'grace_period_ends_on',
        ];

        $scenarios[self::SCENARIO_REHASH] = ['hash'];

        return $scenarios;
    }

    public function extendHibpCheckAfter(): void
    {
        $this->setScenario(self::SCENARIO_UPDATE_METADATA);
        $this->check_hibp_after = MySqlDateTime::relativeTime(\Yii::$app->params['hibpCheckInterval']);
        if (!$this->save()) {
            \Yii::warning([
                'action' => 'extend hibp check after',
                'employee_id' => $this->employee_id,
                'message' => 'unable to update check_hibp_after',
                'errors' => $this->getFirstErrors(),
            ]);
        }
    }

    /**
     * Mark password as pwned by:
     *  - set hibp_is_pwned to yes
     *  - set expiration to now
     *  - set graceperiod based on config
     */
    public function markPwned(): void
    {
        $this->setScenario(self::SCENARIO_UPDATE_METADATA);
        $this->hibp_is_pwned = 'yes';
        $this->expires_on = MySqlDateTime::relativeTime('+5 minutes');
        $this->grace_period_ends_on = MySqlDateTime::relativeTime(\Yii::$app->params['hibpGracePeriod']);
        if (!$this->save()) {
            \Yii::error([
                'action' => 'check and process hibp',
                'employee_id' => $this->user->employee_id,
                'message' => 'unable to force expire a pwned password',
                'errors' => $this->getFirstErrors(),
            ]);
        } else {
            \Yii::warning([
                'action' => 'mark pwned',
                'employee_id' => $this->user->employee_id,
                'message' => 'pwned password detected and processed'
            ]);
        }
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, self::HASH_ALGORITHM, ["cost" => self::HASH_COST]);
    }
}
