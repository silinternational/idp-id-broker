<?php

namespace common\models;

use Exception;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;

/*
 * Email is a Yii2 model for a database email queue
 *
 * Copied from silinternational/email-service
 */
class Email extends EmailBase
{
    /** int $delay number of seconds to delay sending */
    public $delay_seconds = 0;

    public function scenarios()
    {
        return [
            self::SCENARIO_DEFAULT => [
                'to_address',
                'cc_address',
                'bcc_address',
                'subject',
                'text_body',
                'html_body',
                'delay_seconds',
                'send_after',
            ],
        ];
    }

    public function rules()
    {
        return ArrayHelper::merge(
            parent::rules(),
            [
                [
                    'attempts_count', 'default', 'value' => 0,
                ],
                [
                    ['to_address', 'cc_address', 'bcc_address'],
                    'email',
                    'message' => '{attribute} is not a valid email address: {value}',
                ],
                [
                    'text_body', 'required', 'when' => function ($model) {
                        return empty($model->html_body);
                    },
                ],
            ]
        );
    }

    public function behaviors()
    {
        // http://www.yiiframework.com/doc-2.0/yii-behaviors-timestampbehavior.html
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * Attempt to send email. Returns 1 on success.
     * @throws Exception if sending failed
     * @return int number of sent messages
     */
    public function send(): int
    {
        /*
         * Try to send email or throw exception
         */
        $message = $this->getMessage();
        if (!$message->send()) {
            throw new Exception('Unable to send email', 1741067356);
        }

        /*
         * Remove entry from queue (if saved to queue) after successful send
         */
        $this->removeFromQueue();

        /*
         * Log success
         */
        \Yii::info([
            'action' => 'send email',
            'id' => $this->id,
            'to' => $this->to_address,
            'subject' => $this->subject,
            'status' => 'sent',
        ]);
        return 1;
    }

    /**
     * Attempt to send email and on failure update attempts count and save (queue it)
     * @throws Exception
     * @return int number of sent messages
     */
    public function retry(): int
    {
        try {
            return $this->send();
        } catch (Exception $e) {
            /*
             * Send failed, attempt to queue
             */
            $this->attempts_count += 1;
            $this->updated_at = time();

            $log = [
                'action' => 'retry sending email',
                'to' => $this->to_address,
                'subject' => $this->subject,
                'attempts_count' => $this->attempts_count,
                'last_attempt' => $this->updated_at,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
            \Yii::error($log);

            if (!$this->save()) {
                \Yii::error([
                    'action' => 'save email after failed retry failed',
                    'status' => 'error',
                    'error' => $this->getFirstErrors(),
                ]);
                throw new ServerErrorHttpException(
                    'Unable to save email after failing to retry sending. Error: ' .
                    print_r($this->getFirstErrors(), true),
                    1741067362
                );
            }
        }
        return 0;
    }

    /**
     * Builds a mailer object from $this and returns it
     * @return \yii\mail\MessageInterface
     */
    public function getMessage()
    {
        $mailer = \Yii::$app->mailer->compose(
            [
                'html' => '@common/mail/html',
                'text' => '@common/mail/text'
            ],
            [
                'html' => $this->html_body,
                'text' => $this->text_body
            ]
        );
        $from = \Yii::$app->params['fromEmail'];
        $name = \Yii::$app->params['fromName'];
        if (empty($name)) {
            $mailer->setFrom($from);
        } else {
            $mailer->setFrom([$from => $name]);
        }
        $mailer->setTo($this->to_address);
        $mailer->setSubject($this->subject);

        /*
         * Conditionally set optional fields
         */
        $setMethods = [
            'setCc' => $this->cc_address,
            'setBcc' => $this->bcc_address,
        ];
        foreach ($setMethods as $method => $value) {
            if ($value) {
                $mailer->$method($value);
            }
        }

        return $mailer;
    }

    /**
     * Attempt to send messages from queue
     * @throws Exception
     */
    public static function sendQueuedEmail()
    {
        $log = [
            'method' => 'Email::sendQueuedEmail',
        ];
        try {
            $batchSize = \Yii::$app->params['emailQueueBatchSize'];
            $queued = self::find()->orderBy(['updated_at' => SORT_ASC])
                ->where(['<', 'send_after', time()])->orWhere('send_after IS NULL')
                ->limit($batchSize)->all();

            $log += [
                'batchSize' => $batchSize,
                'queuedEmails' => count($queued),
                'sentEmails' => 0,
            ];

            if (empty($queued)) {
                // If nothing queued, no need to send log
                return;
            }

            /** @var Email $email */
            foreach ($queued as $email) {
                $log['sentEmails'] += $email->retry();
            }
        } catch (Exception $e) {
            $log += [
                'status' => 'error',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
            \Yii::error($log);
        }

        // Send log of successful processing
        \Yii::info($log);
    }

    /**
     * If $this has been saved to database, it will be deleted and on failure throw an exception
     * @throws Exception
     */
    private function removeFromQueue()
    {
        try {
            if ($this->id && !$this->delete()) {
                throw new Exception(
                    'Unable to delete email queue entry',
                    1741067370
                );
            }
        } catch (Exception $e) {
            $log = [
                'action' => 'delete after send',
                'status' => 'failed to delete',
                'error' => $e->getMessage(),
            ];
            \Yii::error($log, 'application');

            throw new Exception(
                'Unable to delete email queue entry',
                1741067379
            );
        }
    }

    /**
     * @return array of fields that should be included in responses.
     */
    public function fields(): array
    {
        return [
            'id',
            'to_address',
            'cc_address',
            'bcc_address',
            'subject',
            'text_body',
            'html_body',
            'attempts_count',
            'updated_at',
            'created_at',
            'error',
            'send_after',
        ];
    }

    public function beforeSave($insert)
    {
        if ($this->delay_seconds > 0) {
            $this->send_after = time() + $this->delay_seconds;
        }

        return parent::beforeSave($insert);
    }
}
