<?php

use Sil\PhpEnv\Env;

/* Get frontend-specific config settings from ENV vars or set defaults. */
$frontCookieSecure = Env::get('FRONT_COOKIE_SECURE', true);

$sessionLifetime = 1800; // 30 minutes

const UID_ROUTE_PATTERN = '<uid:([a-zA-Z0-9_\-]{32})>';

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'errorHandler'],
    'controllerNamespace' => 'frontend\controllers',
    'components' => [
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,
        ],
        'session' => [
            'cookieParams' => [// http://us2.php.net/manual/en/function.session-set-cookie-params.php
                'lifetime' => $sessionLifetime,
                'path' => '/',
                'httponly' => true,
                'secure' => $frontCookieSecure,
            ],
        ],
        'log' => [

        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'request' => [
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [

                /*
                 * Status route
                 */
                'GET /site/system-status' => 'site/system-status',

                /*
                 * Catch all to throw 401 or 405
                 */
                '/<url:.*>' => 'site/index',
            ]
        ]
    ],
    'params' => [

    ],
];
