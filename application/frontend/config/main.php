<?php

use Sil\PhpEnv\Env;

/* Get frontend-specific config settings from ENV vars or set defaults. */
$frontCookieSecure = Env::get('FRONT_COOKIE_SECURE', true);

$sessionLifetime = 1800; // 30 minutes

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'], //TODO: is this used?
    'controllerNamespace' => 'frontend\controllers',
    'components' => [
        'errorHandler' => [
            'class' => 'frontend\web\ErrorHandler',
        ],
        'user' => [
            'identityClass' => 'common\models\ApiConsumer',
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,
        ],
        'session' => [
            'cookieParams' => [// http://us2.php.net/manual/en/function.session-set-cookie-params.php
                'lifetime' => $sessionLifetime, //TODO: if we have enableSession=false, is this relevant?
                'path' => '/',
                'httponly' => true,
                'secure' => $frontCookieSecure,
            ],
        ],
        'request' => [
            'enableCsrfValidation' => false,  //TODO: should we be doing this?  Or is it even relevant for this project?
            'parsers' => [
                'application/json' => 'yii\web\JsonParser', // required according to http://www.yiiframework.com/doc-2.0/guide-rest-quick-start.html#enabling-json-input
            ]
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true, //TODO: what does this mean?
            'showScriptName' => false,
            'rules' => [
                /*
                 * Status
                 */
                'GET /site/system-status' => 'site/system-status',

                /*
                 * User
                 */
                'POST /user' => 'user/create',
                'PUT /user/<employeeId:\w+>/password' => 'user/update-password',

                /*
                 * Authentication
                 */
                'POST /authentication' => 'authentication/create',
            ]
        ],
    ],
];
