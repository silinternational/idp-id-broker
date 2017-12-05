<?php
namespace console\controllers;

use common\models\Mfa;
use yii\console\Controller;

class CronController extends Controller
{
    public function actionRemoveOldUnverifiedRecords()
    {
        \Yii::warning([
            'action' => 'delete old unverified mfa records',
            'status' => 'starting',
        ]);
        Mfa::removeOldUnverifiedRecords();
    }
}