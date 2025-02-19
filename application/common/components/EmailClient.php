<?php

namespace common\components;

use common\models\Email;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\db\Exception;

/*
 * EmailClient is a Yii2 component to send email via SMTP or AWS Simple Email Service
 *
 * Derived from EmailController in silinternational/email-service
 */
class EmailClient extends Component
{
    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function email(array $properties): void
    {
        $email = new Email();
        $email->attributes = $properties;

        if (!$email->validate()) {
            throw new Exception(current($email->getFirstErrors()));
        }

        if ((int)$email->send_after <= time() && (int)$email->delay_seconds <= 0) {
            /*
             * Attempt to send email immediately
             */
            try {
                $email->send();
                return;
            } catch (\Exception $e) {
                Yii::error([
                    'action' => 'create email',
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ]);

                // ignore the error, but queue the message
            }
        }

        if (!$email->save()) {
            $details = current($email->getFirstErrors());

            Yii::error([
                'action' => 'create email',
                'status' => 'error',
                'error' => $details,
            ]);

            throw new Exception(current($email->getFirstErrors()));
        }

        Yii::info([
            'action' => 'email/queue',
            'status' => 'queued',
            'id' => $email->id,
            'toAddress' => $email->to_address ?? '(null)',
            'subject' => $email->subject ?? '(null)',
            'send_after' => date('c', $email->send_after),
            'delay_seconds' => $email->delay_seconds,
        ]);
    }
}
