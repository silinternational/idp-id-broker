<?php
namespace console\controllers;

use common\models\Mfa;
use yii\console\Controller;

class CronController extends Controller
{
    public function actionRemoveOldUnverifiedRecords()
    {
        Mfa::removeOldUnverifiedRecords();
    }
}