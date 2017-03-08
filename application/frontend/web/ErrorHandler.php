<?php

namespace frontend\web;

use Yii;
use yii\web\Response;

class ErrorHandler extends \yii\web\ErrorHandler
{
    protected function renderException($exception)
    {
        $response = Yii::$app->getResponse() ?? new Response();

        $response->format = Response::FORMAT_JSON;

        $response->data = parent::convertExceptionToArray($exception);

        $response->setStatusCode($exception->statusCode ?? 500);

        $response->send();
    }
}
