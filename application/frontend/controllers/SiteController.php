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
            'status'
        ];

        return $behaviors;
    }

    public function actionStatus()
    {
        try {
            // db comms are a good indication of health
            Yii::$app->db->open();
        } catch (Exception $e) {
            Yii::error('Database problem: ' . $e->getMessage());
            throw new ServerErrorHttpException(
                'Database connection problem.', $e->getCode()
            );
        }
        
        try {
            Yii::$app->emailer->getSiteStatus();
        } catch (Exception $e) {
            Yii::error('Email Service problem: ' . $e->getMessage());
            throw new ServerErrorHttpException(
                'Email Service seems to be down.', $e->getCode()
            );
        }

        Yii::$app->response->statusCode = 204;
    }

    public function actionUndefinedRequest()
    {
        $method = Yii::$app->request->method;
        $url    = Yii::$app->request->url;

        Yii::warning("$method $url requested but not defined.");

        throw new NotFoundHttpException();
    }
}
