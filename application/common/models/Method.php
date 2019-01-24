<?php

namespace common\models;

use common\components\Emailer;
use common\exceptions\InvalidCodeException;
use common\helpers\MySqlDateTime;
use common\helpers\Utils;
use yii\helpers\ArrayHelper;
use yii\web\ConflictHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class Method
 * @package common\models
 *
 */
class Method extends MethodBase
{
    public function rules()
    {
        return ArrayHelper::merge(
            [
                [
                    ['uid'], 'default', 'value' => Utils::generateRandomString(),
                ],

                [
                    ['verified', 'verification_attempts'], 'default', 'value' => 0,
                ],

                [
                    'verification_code', 'default', 'when' => function () {
                        return $this->getIsNewRecord() && $this->verified != 1;
                    },
                    'value' => $this->createCode(),
                ],

                [
                    'verification_expires', 'default', 'when' => function () {
                        return $this->getIsNewRecord() && $this->verified != 1;
                    },
                    'value' => $this->calculateExpirationDate(),
                ],

                [
                    'value', 'email',
                ],

                [
                    ['created'], 'default', 'value' => MySqlDateTime::now(),
                ],

            ],
            parent::rules()
        );
    }

    public function fields()
    {
        return [
            'id' => function () {
                return $this->uid;
            },
            'value',
            'verified' => function () {
                return $this->verified == 1;
            },
            'created' => function ($model) {
                return Utils::getIso8601($model->created);
            },
        ];
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getMaskedValue()
    {
        return Utils::maskEmail($this->value);
    }

    public function isVerified()
    {
        return $this->verified === 1 ? true : false;
    }

    /**
     * Send verification message
     * @throws ConflictHttpException
     * @throws ServerErrorHttpException
     */
    public function sendVerification()
    {
        if ($this->isVerified()) {
            throw new ConflictHttpException('Method already verified', 1540689804);
        }

        $this->incrementVerificationAttempts();

        $this->sendVerificationEmail();
    }

    /**
     * Send a verification email message
     */
    public function sendVerificationEmail()
    {
        /* @var $emailer Emailer */
        $emailer = \Yii::$app->emailer;

        $data = [
            'toAddress' => $this->value,
            'code' => $this->verification_code,
            'uid' => $this->uid,
        ];

        $emailer->sendMessageTo(EmailLog::MESSAGE_TYPE_METHOD_VERIFY, $this->user, $data);
    }

    /**
     * Update record to be verified
     * @throws \Exception
     */
    public function setAsVerified()
    {
        /*
         * Update attributes to be verified
         */
        $this->verification_code = null;
        $this->verification_expires = null;
        $this->verification_attempts = null;
        $this->verified = 1;

        if (! $this->save()) {
            \Yii::error([
                'action' => 'validate and set method as verified',
                'status' => 'error',
                'error' => $this->getFirstErrors(),
            ]);
            throw new \Exception('Unable to set method as verified', 1461442990);
        }
    }

    /**
     * Delete all method records that are not verified and verification_expires date is in the past
     * by more than a configured "grace period."
     */
    public static function deleteExpiredUnverifiedMethods()
    {
        /*
         * Replace '+' with '-' so all env parameters can be defined consistently as '+n unit'
         */
        $methodGracePeriod = str_replace('+', '-', \Yii::$app->params['method']['gracePeriod']);

        /**
         * @var string $removeExpireBefore   All unverified records that expired before this date
         * should be deleted. Calculated relative to now (time of execution).
         */
        $removeExpireBefore = MySqlDateTime::relativeTime($methodGracePeriod);
        $methods = self::find()
            ->where(['verified' => 0])
            ->andWhere(['<', 'verification_expires', $removeExpireBefore])
            ->all();

        $numDeleted = 0;
        foreach ($methods as $method) {
            try {
                if ($method->delete() !== false) {
                    $numDeleted += 1;
                }
            } catch (\Exception $e) {
                \Yii::error([
                    'action' => 'delete expired unverified methods',
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'method_uid' => $method->uid,
                ]);
            }
        }

        \Yii::warning([
            'action' => 'delete old unverified method records',
            'status' => 'complete',
            'count' => $numDeleted,
        ]);
    }

    /**
     * Create new password recovery method, normally un-verified, and send a
     * verification message to the user. If a matching record already exists,
     * record creation is bypassed. If 'created' parameter is specified,
     * then the record is created pre-verified and no message is sent to the
     * user.
     *
     * @param integer $userId
     * @param string $value
     * @param string $created Date recovery method was created, in MySQL datetime format.
     * @return Method
     * @throws ConflictHttpException
     * @throws ServerErrorHttpException
     */
    public static function findOrCreate($userId, $value, $created = '')
    {
        $method = Method::findOne(['value' => $value, 'user_id' => $userId]);

        if ($method === null) {
            $method = new Method();
            $method->user_id = $userId;
            $method->value = mb_strtolower($value);
            if (! empty($created)) {
                $method->created = $created;
                $method->verified = 1;
            }
        } else {
            if (! $method->isVerified()) {
                $method->restartVerification();
            }
            return $method;
        }

        if (! $method->save()) {
            throw new ServerErrorHttpException(
                sprintf('Unable to save new method'),
                1461441851
            );
        }

        if (empty($created)) {
            $method->sendVerification();
        }

        return $method;
    }

    /**
     * Change code, update expiration date, and send a new verification message.
     *
     * @throws ServerErrorHttpException
     */
    public function restartVerification()
    {
        $this->verified = 0;
        $this->verification_attempts = 1;
        $this->verification_code = $this->createCode();
        $this->verification_expires = $this->calculateExpirationDate();

        if (! $this->save()) {
            throw new ServerErrorHttpException('Save error while restarting verification', 1545154473);
        }

        $this->sendVerificationEmail();
    }

    /**
     * Generate and return a new verification code
     *
     * @return string
     */
    protected function createCode(): string
    {
        return Utils::getRandomDigits(\Yii::$app->params['method']['codeLength']);
    }

    /**
     * Calculate and return a new expiration date for the verification code
     *
     * @return string
     */
    protected function calculateExpirationDate(): string
    {
        return MySqlDateTime::relativeTime(\Yii::$app->params['method']['lifetime']);
    }

    /**
     * Test verification expiration against the current time. If the time has passed,
     * return true. Otherwise, return false.
     *
     * @return bool
     * @throws \Exception
     */
    public function isVerificationExpired(): bool
    {
        return MySqlDateTime::isBefore($this->verification_expires, time());
    }

    /**
     * Check provided code against the stored code. If not matching, throw an exception.
     *
     * @param $userSubmitted
     * @throws InvalidCodeException
     * @throws ServerErrorHttpException
     */
    public function validateProvidedCode($userSubmitted): void
    {
        $this->incrementVerificationAttempts();

        if ($this->verification_code !== $userSubmitted) {
            throw new InvalidCodeException('Invalid verification code', 1461442988);
        }
    }

    /**
     * Increment verification attempts and save to database.
     *
     * @throws ServerErrorHttpException
     */
    protected function incrementVerificationAttempts(): void
    {
        $this->verification_attempts++;
        if (!$this->save()) {
            throw new ServerErrorHttpException('Save error after incrementing attempts', 1461441850);
        }
    }
}
