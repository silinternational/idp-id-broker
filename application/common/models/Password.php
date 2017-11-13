<?php

namespace common\models;

use Closure;
use common\helpers\MySqlDateTime;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class Password
 * @package common\models
 *
 * @property User $user
 */
class Password extends PasswordBase
{
    const DATE_FORMAT = 'Y-m-d 23:59:59 \G\M\T';

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
                'password', 'required',
            ],
            [
                'password', 'string',
            ],
            [
                'hash', 'default', 'value' => function () {
                    return password_hash($this->password, PASSWORD_DEFAULT);
                 },
            ],
            [
                'hash', $this->isHashable(),
            ],
            [
                'hash', $this->isReusable(),
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

    private function isReusable(): Closure
    {
        return function ($attributeName) {
            if ($this->hasAlreadyBeenUsedTooRecently()) {
                $this->addError($attributeName, 'May not be reused yet.');
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
        return function() {
            $lifespan = Yii::$app->params['passwordLifespan'];

            return MySqlDateTime::formatDate(strtotime($lifespan, strtotime($this->created_utc)));
        };
    }

    private function gracePeriodEnds(): Closure
    {
        return function() {
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
                return "{$model->created_utc} UTC";
            },
            'expires_on' => function (Password $model) {
                return $model->getExpiresOn();
            },
            'grace_period_ends_on' => function ($model) {
                return $model->getGracePeriodEndsOn();
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
     * Calculate expires_on date based on if user has MFA configured
     * @return string
     */
    public function getExpiresOn()
    {
        if (count($this->user->mfas) > 0) {
            $expiresOnTimestamp = strtotime($this->expires_on . ' 23:59:59 UTC');
            $extendedTimestamp = strtotime(\Yii::$app->params['passwordMfaLifespanExtension'], $expiresOnTimestamp);
            return date(self::DATE_FORMAT, $extendedTimestamp);
        }
        return $this->expires_on . ' 23:59:59 UTC';
    }

    /**
     * Calculate grace_period_ends_on based on if user has MFA configured
     * @return string
     */
    public function getGracePeriodEndsOn()
    {
        if (count($this->user->mfas) > 0) {
            $graceEndsOnTimestamp = strtotime($this->grace_period_ends_on . ' 23:59:59 UTC');
            $extendedTimestamp = strtotime(\Yii::$app->params['passwordMfaLifespanExtension'], $graceEndsOnTimestamp);
            return date(self::DATE_FORMAT, $extendedTimestamp);
        }
        return $this->grace_period_ends_on . ' 23:59:59 UTC';
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
