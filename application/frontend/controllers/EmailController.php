<?php

namespace frontend\controllers;

use common\models\Email;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\web\UnprocessableEntityHttpException;

class EmailController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Use request header-> 'Authorization: Bearer <token>'
        $behaviors['authenticator']['class'] = HttpBearerAuth::class;

        return $behaviors;
    }

    public function actionQueue(): Email
    {
        $email = new Email();
        $email->attributes = Yii::$app->request->getBodyParams();

        if (!$email->validate()) {
            throw new UnprocessableEntityHttpException(current($email->getFirstErrors()));
        }

        if ((int)$email->send_after <= time() && (int)$email->delay_seconds <= 0) {
            /*
             * Attempt to send email immediately
             */
            try {
                $email->send();
                return $email;
            } catch (\Exception $e) {
                // ignore for now, will queue
            }
        }

        if (!$email->save()) {
            $details = current($email->getFirstErrors());

            Yii::error([
                'action' => 'create email',
                'status' => 'error',
                'error' => $details
            ]);

            throw new UnprocessableEntityHttpException(current($email->getFirstErrors()));
        }

        Yii::info([
            'action' => 'email/queue',
            'status' => 'queued',
            'id' => $email->id,
            'toAddress' => $email->to_address ?? '(null)',
            'subject' => $email->subject ?? '(null)',
            'send_after' => date('c', $email->send_after),
        ]);

        return $email;
    }
}
