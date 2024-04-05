<?php

namespace frontend\controllers;

use Exception;
use frontend\components\BaseRestController;
use GuzzleHttp\Command\Exception\CommandException as GuzzleCommandException;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException as Http500;

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
        /* @var $webApp yii\web\Application */
        $webApp = Yii::$app;

        try {
            $dbComponent = $webApp->get('db');
        } catch (Exception $e) {
            Yii::error('DB config problem: ' . $e->getMessage());
            throw new Http500('DB config problem.');
        }

        try {
            $dbComponent->open();
        } catch (Exception $e) {
            Yii::error('DB connection problem: ' . $e->getMessage());
            throw new Http500('DB connection problem.', $e->getCode());
        }


        try {
            $emailer = $webApp->get('emailer');
        } catch (Exception $e) {
            Yii::error('Emailer config problem: ' . $e->getMessage());
            throw new Http500('Emailer config problem.');
        }

        try {
            $webApp->get('totp');
        } catch (Exception $e) {
            Yii::error('TOTP config problem: ' . $e->getMessage());
            throw new Http500('TOTP config problem.');
        }

        try {
            $webApp->get('webauthn');
        } catch (Exception $e) {
            Yii::error('Webauthn config problem: ' . $e->getMessage());
            throw new Http500('Webauthn config problem.');
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
