<?php

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    // http://www.yiiframework.com/doc-2.0/guide-structure-applications.html#controllerNamespace
    'controllerNamespace' => 'frontend\controllers',
    'components' => [
        // http://www.yiiframework.com/doc-2.0/guide-security-authentication.html
        'user' => [
            'identityClass' => 'common\models\ApiConsumer', // custom Bearer <token> implementation
            'enableSession' => false, // ensure statelessness
        ],
        // http://www.yiiframework.com/doc-2.0/guide-runtime-requests.html
        'request' => [
            // restrict input to JSON only http://www.yiiframework.com/doc-2.0/guide-rest-quick-start.html#enabling-json-input
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        // http://www.yiiframework.com/doc-2.0/guide-runtime-responses.html
        'response' => [
            // all responses, even unhandled errors, need to be in JSON for an API.
            'format' => yii\web\Response::FORMAT_JSON,
        ],
        // http://www.yiiframework.com/doc-2.0/guide-runtime-routing.html
        'urlManager' => [
            'enablePrettyUrl' => true, // turns /index.php?r=post%2Fview&id=100 into /index.php/post/100
            'showScriptName' => false, // turns /index.php/post/100 into /post/100
            'rules' => [
                'POST /authentication'                 => 'authentication/create',
                'POST /user'                           => 'user/create',
                'PUT  /user/<employeeId:\w+>/password' => 'user/update-password',
                'GET  /site/system-status'             => 'site/system-status',
            ]
        ],
    ],
];
