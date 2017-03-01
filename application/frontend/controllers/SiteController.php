<?php
namespace frontend\controllers;

use frontend\components\BaseRestController;
use yii\web\ServerErrorHttpException;

class SiteController extends BaseRestController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // bypass authentication
        $behaviors['authenticator']['except'] = [
            'system-status'
        ];

        return $behaviors;
    }

    public function actionSystemStatus()
    {
        try {
            // db comms are a good indication of health
            \Yii::$app->db->open();

            \Yii::$app->response->statusCode = 204;
        } catch (\Exception $e) {
            throw new ServerErrorHttpException(
                'Database connection problem.', $e->getCode()
            );
        }
    }
}
