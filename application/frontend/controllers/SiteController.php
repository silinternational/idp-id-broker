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
            $emailer->getSiteStatus();
        } catch (GuzzleCommandException $e) {
            $response = $e->getResponse();
            if ($response) {
                $responseBody = $response->getBody();
                if ($responseBody) {
                    $responseContents = $responseBody->getContents();
                }
                $responseHeaders = $response->getHeaders();
            }
            Yii::error([
                'event' => 'email service guzzle command error',
                'errorCode' => $e->getCode(),
                'errorMessage' => $e->getMessage(),
                'responseHeaders' => $responseHeaders ?? null,
                'responseContents' => $responseContents ?? null,
                'stackTrace' => $e->getTrace(),
            ]);
            throw new Http500('Email Service problem.', $e->getCode());
        } catch (Exception $e) {
            Yii::error([
                'event' => 'email service status error',
                'exceptionClass' => get_class($e),
                'errorCode' => $e->getCode(),
                'errorMessage' => $e->getMessage(),
            ]);
            throw new Http500('Email Service problem.', $e->getCode());
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
