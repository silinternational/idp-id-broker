<?php

use common\components\Emailer;
use common\ldap\Ldap;
use Sil\JsonLog\target\JsonSyslogTarget;
use Sil\JsonLog\target\EmailServiceTarget;
use Sil\PhpEnv\Env;
use Sil\Psr3Adapters\Psr3Yii2Logger;
use yii\db\Connection;
use yii\helpers\Json;
use yii\web\Request;

$idpName       = Env::requireEnv('IDP_NAME');
$mysqlHost     = Env::requireEnv('MYSQL_HOST');
$mysqlDatabase = Env::requireEnv('MYSQL_DATABASE');
$mysqlUser     = Env::requireEnv('MYSQL_USER');
$mysqlPassword = Env::requireEnv('MYSQL_PASSWORD');

$notificationEmail = Env::get('NOTIFICATION_EMAIL');

/*
 * If using Email Service, the following ENV vars should be set:
 *  - EMAIL_SERVICE_accessToken
 *  - EMAIL_SERVICE_assertValidIp
 *  - EMAIL_SERVICE_baseUrl
 *  - EMAIL_SERVICE_validIpRanges
 */
$emailServiceConfig = Env::getArrayFromPrefix('EMAIL_SERVICE_');

// Re-retrieve the validIpRanges as an array.
$emailServiceConfig['validIpRanges'] = Env::getArray('EMAIL_SERVICE_validIpRanges');

return [
    'id' => 'app-common',
    'bootstrap' => ['log'],
    'components' => [
        'db' => [
            'class' => Connection::class,
            'dsn' => "mysql:host=$mysqlHost;dbname=$mysqlDatabase",
            'username' => $mysqlUser,
            'password' => $mysqlPassword,
            'charset' => 'utf8',
        ],
        'emailer' => [
            'class' => Emailer::class,
            'emailServiceConfig' => $emailServiceConfig,
            
            'sendInviteEmails' => Env::get('SEND_INVITE_EMAILS', false),
            'sendWelcomeEmails' => Env::get('SEND_WELCOME_EMAILS', false),
            
            'subjectForInvite' => Env::get('SUBJECT_FOR_INVITE'),
            'subjectForWelcome' => Env::get('SUBJECT_FOR_WELCOME'),
        ],
        'ldap' => [
            'class' => Ldap::class,
            'acct_suffix' => Env::get('LDAP_ACCT_SUFFIX'),
            'domain_controllers' => explode('|', Env::get('LDAP_DOMAIN_CONTROLLERS')),
            'base_dn' => Env::get('LDAP_BASE_DN'),
            'admin_username' => Env::get('LDAP_ADMIN_USERNAME'),
            'admin_password' => Env::get('LDAP_ADMIN_PASSWORD'),
            'use_ssl' => Env::get('LDAP_USE_SSL', true),
            'use_tls' => Env::get('LDAP_USE_TLS', true),
            'timeout' => Env::get('LDAP_TIMEOUT', 5),
            'logger' => new Psr3Yii2Logger(),
        ],
        // http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
        'log' => [
            'targets' => [
                [
                    'class' => JsonSyslogTarget::class,
                    'categories' => ['application'], // stick to messages from this app, not all of Yii's built-in messaging.
                    'logVars' => [], // no need for default stuff: http://www.yiiframework.com/doc-2.0/yii-log-target.html#$logVars-detail
                    'prefix' => function () {
                        //TODO: assumes yii\web\Request here, could be a problem if app
                        //    develops a console portion since there's also a
                        //    yii\console\Request
                        /* @var Request */
                        $request = Yii::$app->request;

                        // Assumes format: Bearer consumer-module-name-32randomcharacters
                        $requesterId = substr($request->headers['Authorization'], 7, 16) ?: 'unknown';

                        $prefixData = [
                            'env' => YII_ENV,
                            'id' => $requesterId,
                            'ip' => $request->getUserIP(),
                        ];

                        return Json::encode($prefixData);
                    },
                ],
                [
                    'class' => EmailServiceTarget::class,
                    'categories' => ['application'], // stick to messages from this app, not all of Yii's built-in messaging.
                    'enabled' => !empty($notificationEmail),
                    'except' => [
                        'yii\web\HttpException:400',
                        'yii\web\HttpException:401',
                        'yii\web\HttpException:404',
                        'yii\web\HttpException:409',
                        'Sil\EmailService\Client\EmailServiceClientException',
                    ],
                    'levels' => ['error'],
                    'logVars' => [], // Disable logging of _SERVER, _POST, etc.
                    'message' => [
                        'to' => $notificationEmail,
                        'subject' => 'ERROR - ' . $idpName . ' ID Broker [' . YII_ENV .']',
                    ],
                    'baseUrl' => $emailServiceConfig['baseUrl'],
                    'accessToken' => $emailServiceConfig['accessToken'],
                    'assertValidIp' => $emailServiceConfig['assertValidIp'],
                    'validIpRanges' => $emailServiceConfig['validIpRanges'],
                    'prefix' => function ($message) {
                        $prefixData = [
                            'env' => YII_ENV,
                        ];
                        
                        try {
                            $request = \Yii::$app->request;
                            $prefixData['url'] = $request->getUrl();
                            $prefixData['method'] = $request->getMethod();
                        } catch (\Exception $e) {
                            $prefixData['url'] = 'not available';
                        }
                        
                        return Json::encode($prefixData);
                    },
                ],
            ],
        ],
    ],
    'params' => [
        'migratePasswordsFromLdap' => Env::get('MIGRATE_PW_FROM_LDAP', false),
    ],
];
