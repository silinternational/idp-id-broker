<?php
namespace frontend\controllers;

use Exception;
use frontend\components\BaseRestController;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class SiteController extends BaseRestController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator']['except'] = [
            // bypass authentication, i.e., public API
            'system-status'
        ];

        return $behaviors;
    }

    public function actionSystemStatus()
    {
        try {
            // db comms are a good indication of health
            Yii::$app->db->open();

            Yii::$app->response->statusCode = 204;
        } catch (Exception $e) {
            throw new ServerErrorHttpException(
                'Database connection problem.', $e->getCode()
            );
        }
    }

    public function actionUndefinedRequest()
    {
        throw new NotFoundHttpException();
    }
}
