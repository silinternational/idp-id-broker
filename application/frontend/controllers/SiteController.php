<?php

namespace frontend\controllers;

use frontend\components\BaseRestController;
use Throwable;
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

    /**
     * @throws ServerErrorHttpException
     */
    public function actionStatus()
    {
        /* @var $webApp yii\web\Application */
        $webApp = Yii::$app;

        try {
            $dbComponent = $webApp->get('db');
        } catch (Throwable $t) {
            $msg = sprintf('DB config error (%s:%d): %s', $t->getFile(), $t->getLine(), $t->getMessage());
            Yii::error($msg);
            throw new ServerErrorHttpException('DB config error.', $t->getCode(), $t);
        }

        try {
            $dbComponent->open();
        } catch (Throwable $t) {
            $msg = sprintf('DB connection error (%s:%d): %s', $t->getFile(), $t->getLine(), $t->getMessage());
            Yii::error($msg);
            throw new ServerErrorHttpException('DB connection error.', $t->getCode(), $t);
        }

        try {
            $webApp->get('emailer');
        } catch (Throwable $t) {
            $msg = sprintf('Emailer error (%s:%d): %s', $t->getFile(), $t->getLine(), $t->getMessage());
            Yii::error($msg);
            throw new ServerErrorHttpException('Emailer error.', $t->getCode(), $t);
        }

        try {
            $webApp->get('totp');
        } catch (Throwable $t) {
            $msg = sprintf('TOTP error (%s:%d): %s', $t->getFile(), $t->getLine(), $t->getMessage());
            Yii::error($msg);
            throw new ServerErrorHttpException('TOTP error.', $t->getCode(), $t);
        }

        try {
            $webApp->get('webauthn');
        } catch (Throwable $t) {
            $msg = sprintf('Webauthn error (%s:%d): %s', $t->getFile(), $t->getLine(), $t->getMessage());
            Yii::error($msg);
            throw new ServerErrorHttpException('Webauthn error.', $t->getCode(), $t);
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
