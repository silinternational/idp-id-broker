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
            throw new ServerErrorHttpException(
                'Database connection problem.', $e->getCode()
            );
        }

        Yii::$app->response->statusCode = 204;
    }

    public function actionUndefinedRequest()
    {
//TODO: Yii::warning with details of current environment and request to identify either trends of either potential attacks or API misunderstandings.
        throw new NotFoundHttpException();
    }
}
