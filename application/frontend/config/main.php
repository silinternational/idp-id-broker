<?php

use common\models\ApiConsumer;
use Sil\PhpEnv\Env;
use yii\web\JsonParser;
use yii\web\Response;

$cookieValidationKey = Env::get('COOKIE_VALIDATION_KEY');

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    // http://www.yiiframework.com/doc-2.0/guide-structure-applications.html#controllerNamespace
    'controllerNamespace' => 'frontend\controllers',
    'components' => [
        // http://www.yiiframework.com/doc-2.0/guide-security-authentication.html
        'user' => [
            'identityClass' => ApiConsumer::class, // custom Bearer <token> implementation
            'enableSession' => false, // ensure statelessness
        ],
        // http://www.yiiframework.com/doc-2.0/guide-runtime-requests.html
        'request' => [
            'cookieValidationKey' => $cookieValidationKey,
            'enableCookieValidation' => !empty($cookieValidationKey),
            // restrict input to JSON only http://www.yiiframework.com/doc-2.0/guide-rest-quick-start.html#enabling-json-input
            'parsers' => [
                'application/json' => JsonParser::class,
            ]
        ],
        // http://www.yiiframework.com/doc-2.0/guide-runtime-responses.html
        'response' => [
            // all responses, even unhandled errors, need to be in JSON for an API.
            'format' => Response::FORMAT_JSON,
        ],
        // http://www.yiiframework.com/doc-2.0/guide-runtime-routing.html
        'urlManager' => [
            'enablePrettyUrl' => true, // turns /index.php?r=post%2Fview&id=100 into /index.php/post/100
            'showScriptName' => false, // turns /index.php/post/100 into /post/100
            // http://www.yiiframework.com/doc-2.0/guide-rest-routing.html
            // http://www.yiiframework.com/doc-2.0/guide-runtime-routing.html#named-parameters
            'rules' => [
                'GET  user'                           => 'user/index',
                'GET  user/expiring'                  => 'user/expiring',
                'GET  user/first-password'            => 'user/first-password',
                'GET  user/<employeeId:\w+>'          => 'user/view',
                'POST user'                           => 'user/create',
                'PUT  user/<employeeId:\w+>'          => 'user/update',
                'PUT  user/<employeeId:\w+>/password' => 'user/update-password',

                'POST authentication' => 'authentication/create',

                'GET    user/<employeeId:\w+>/mfa'  => 'mfa/list',
                'POST   mfa'                        => 'mfa/create',
                'POST   mfa/<id:\d+>/verify'            => 'mfa/verify',
                'DELETE mfa/<id:\d+>'                   => 'mfa/delete',

                'site/status' => 'site/status',

                '<undefinedRequest>' => 'site/undefined-request',
            ]
        ],
    ],
    'params' => [

    ],
];
