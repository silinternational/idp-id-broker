<?php

use common\models\ApiConsumer;
use Sil\PhpEnv\Env;
use yii\web\JsonParser;
use yii\web\Response;

const UID_ROUTE_PATTERN = '<uid:([a-zA-Z0-9_\-]{32})>';

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
            'cache' => null,
            'enablePrettyUrl' => true, // turns /index.php?r=post%2Fview&id=100 into /index.php/post/100
            'showScriptName' => false, // turns /index.php/post/100 into /post/100
            // http://www.yiiframework.com/doc-2.0/guide-rest-routing.html
            // http://www.yiiframework.com/doc-2.0/guide-runtime-routing.html#named-parameters
            'rules' => [
                /*
                 * User routes
                 */
                'GET  user'                                     => 'user/index',
                'GET  user/<employeeId:\w+>'                    => 'user/view',
                'POST user'                                     => 'user/create',
                'PUT  user/<employeeId:\w+>'                    => 'user/update',
                'PUT  user/<employeeId:\w+>/update-last-login'  => 'user/update-last-login',
                'PUT  user/<employeeId:\w+>/password'           => 'user/update-password',
                'PUT  user/<employeeId:\w+>/password/assess'    => 'user/assess-password',

                /*
                 * Authentication routes
                 */
                'POST authentication' => 'authentication/create',

                /*
                 * MFA routes
                 */
                'GET    user/<employeeId:\w+>/mfa'                 => 'mfa/list',
                'POST   mfa'                                       => 'mfa/create',
                'PUT    mfa/<id:\d+>'                              => 'mfa/update',
                'PUT    mfa/<mfaId:\d+>/webauthn/<webauthnId:\d+>' => 'mfa/update-webauthn',
                'POST   mfa/<id:\d+>/verify'                       => 'mfa/verify',
                'POST   mfa/<id:\d+>/verify/'                      => 'mfa/verify',
                'POST   mfa/<id:\d+>/verify/<type>'                => 'mfa/verify',
                'DELETE mfa/<id:\d+>'                              => 'mfa/delete',
                'DELETE mfa/<mfaId:\d+>/webauthn/<webauthnId:\d+>' => 'mfa/delete-credential',

                /*
                 * Method routes
                 */
                'GET     user/<employeeId:\w+>/method'            => 'method/list',
                'GET     method/' . UID_ROUTE_PATTERN             => 'method/view',
                'POST    method'                                  => 'method/create',
                'PUT     method/' . UID_ROUTE_PATTERN             => 'method/update',
                'PUT     method/' . UID_ROUTE_PATTERN . '/resend' => 'method/resend',
                'PUT     method/' . UID_ROUTE_PATTERN . '/verify' => 'method/verify',
                'DELETE  method/' . UID_ROUTE_PATTERN             => 'method/delete',

                'POST email' => 'email/queue',

                'site/status' => 'site/status',

                '<undefinedRequest>' => 'site/undefined-request',
            ]
        ],
    ],
    'params' => [

    ],
];
