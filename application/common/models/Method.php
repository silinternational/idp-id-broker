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
                    'verification_code', 'default', 'when' => function() { return $this->getIsNewRecord(); },
                    'value' => Utils::getRandomDigits(\Yii::$app->params['reset']['codeLength']),
                ],

                [
                    'verification_expires', 'default', 'when' => function() { return $this->getIsNewRecord(); },
                    'value' => MySqlDateTime::formatDateTime(time() + \Yii::$app->params['reset']['lifetimeSeconds']),
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
            'id' => function() { return $this->uid; },
            'value',
            'verified' => function() { return $this->verified == 1; }
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

        $this->verification_attempts++;
        if ( ! $this->save()) {
            throw new ServerErrorHttpException('Save error after incrementing attempts', 1461441850);
        }

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
        ];

        $emailer->sendMessageTo(EmailLog::MESSAGE_TYPE_METHOD_VERIFY, $this->user, $data);
    }

    /**
     * Validate user submitted code and update record to be verified if valid
     * @param string $userSubmitted
     * @throws \Exception
     */
    public function validateAndSetAsVerified($userSubmitted)
    {
        /*
         * Increase attempts count before verifying code in case verification fails
         * for some reason
         */
        $this->verification_attempts++;
        if ( ! $this->save()) {
            throw new \Exception('Unable to increment verification attempts', 1462903086);
        }

        /*
         * Verify user provided code
         */
        if ( $this->verification_code !== $userSubmitted) {
            throw new InvalidCodeException('Invalid verification code', 1461442988);
        }

        /*
         * Update attributes to be verified
         */
        $this->verification_code = null;
        $this->verification_expires = null;
        $this->verification_attempts = null;
        $this->verified = 1;

        if ( ! $this->save()) {
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
     */
    public static function deleteExpiredUnverifiedMethods()
    {
        $methods = self::find()
            ->where(['verified' => 0])
            ->andWhere(['<', 'verification_expires', MySqlDateTime::now()])
            ->all();

        foreach ($methods as $method) {
            try {
                $method->delete();
            } catch (\Exception $e) {
                \Yii::error([
                    'action' => 'delete expired unverified methods',
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'method_uid' => $method->uid,
                ]);
            }
        }
    }

    /**
     * @param integer $userId
     * @param string $value
     * @return Method
     * @throws ConflictHttpException
     * @throws ServerErrorHttpException
     */
    public static function findOrCreate($userId, $value)
    {
        $method = Method::findOne(['value' => $value, 'user_id' => $userId]);

        if ($method === null) {
            $method = new Method();
            $method->user_id = $userId;
            $method->value = mb_strtolower($value);
        }

        if ( ! $method->save()) {
            throw new ServerErrorHttpException(
                sprintf('Unable to save new method'),
                1461441851
            );
        }

        $method->sendVerification();

        return $method;
    }
}
