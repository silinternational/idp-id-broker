<?php

namespace common\models;

use yii\helpers\ArrayHelper;
use common\helpers\MySqlDateTime;
use common\helpers\Utils;

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
                    'value' => MySqlDateTime::formatDate(time() + \Yii::$app->params['reset']['lifetimeSeconds']),
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
            'uid',
            'value',
        ];
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
     * Send verification message
     * @throws ConflictHttpException
     * @throws ServerErrorHttpException
     */
    public function sendVerification()
    {
        if ($method->isVerified()) {
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
        Verification::sendEmail(
            $this->value,
            'Verification required - New account recovery method added',
            '@common/mail/method/verify',
            $this->verification_code,
            Utils::getFriendlyDate($this->verification_expires),
            $this->user,
            null,
            $this->user->getId(),
            'New email method',
            'A new email method has been added and verification sent to ' . $this->getMaskedValue(),
            []
        );
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getMaskedValue()
    {
        return Utils::maskEmail($this->value);
    }
}
