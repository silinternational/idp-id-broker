<?php

namespace console\controllers;

use common\models\Email;
use yii\console\Controller;

class SendController extends Controller
{
    public function actionSendQueuedEmail()
    {
        Email::sendQueuedEmail();
    }
}
