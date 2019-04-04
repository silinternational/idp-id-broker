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
                'password', 'checkRecentlyUsed',
            ],
            [
                'hash', 'default', 'value' => function () {
                    return password_hash($this->password, PASSWORD_DEFAULT);
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
                return Utils::getIso8601($model->created_utc);
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
     * Returns a date extended by the MFA Lifespan Extension, if applicable
     * @param string $date date in yyyy-mm-dd format
     * @return string conditionally extended and converted to ISO8601 format
     */
    protected function getMfaExtendedDate($date)
    {
        $dateIso = $date . 'T23:59:59Z';
        if (count($this->user->mfas) > 0) {
            $extended = strtotime(\Yii::$app->params['passwordMfaLifespanExtension'], strtotime($dateIso));
            return Utils::getIso8601($extended);
        }
        return $dateIso;
    }

    /**
     * Calculate expires_on date based on if user has MFA configured
     * @return string
     */
    public function getExpiresOn()
    {
        return $this->getMfaExtendedDate($this->expires_on);
    }

    /**
     * Calculate grace_period_ends_on based on if user has MFA configured
     * @return string
     */
    public function getGracePeriodEndsOn()
    {
        return $this->getMfaExtendedDate($this->grace_period_ends_on);
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
}
