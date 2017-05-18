<?php

use common\ldap\Ldap;
use Sil\JsonSyslog\JsonSyslogTarget;
use Sil\Log\EmailTarget;
use Sil\PhpEnv\Env;
use yii\db\Connection;
use yii\helpers\Json;
use yii\swiftmailer\Mailer;
use yii\web\Request;

$idpName       = null;
$mysqlHost     = null;
$mysqlDatabase = null;
$mysqlUser     = null;
$mysqlPassword = null;

$idpName       = Env::requireEnv('IDP_NAME');
$mysqlHost     = Env::requireEnv('MYSQL_HOST');
$mysqlDatabase = Env::requireEnv('MYSQL_DATABASE');
$mysqlUser     = Env::requireEnv('MYSQL_USER');
$mysqlPassword = Env::requireEnv('MYSQL_PASSWORD');

$mailerUseFiles    = Env::get('MAILER_USEFILES', false);
$mailerHost        = Env::get('MAILER_HOST');
$mailerUsername    = Env::get('MAILER_USERNAME');
$mailerPassword    = Env::get('MAILER_PASSWORD');
$notificationEmail = Env::get('NOTIFICATION_EMAIL', 'oncall@example.org');

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
                    'class' => EmailTarget::class,
                    'categories' => ['application'], // stick to messages from this app, not all of Yii's built-in messaging.
                    'logVars' => [], // no need for default stuff: http://www.yiiframework.com/doc-2.0/yii-log-target.html#$logVars-detail
                    'levels' => ['error'],
                    'message' => [
                        'from' => $mailerUsername,
                        'to' => $notificationEmail,
                        'subject' => "ERROR - $idpName-id-broker [".YII_ENV."] Error",
                    ],
                ],
            ],
        ],
        'mailer' => [
            'class' => Mailer::class,
            'useFileTransport' => $mailerUseFiles,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => $mailerHost,
                'username' => $mailerUsername,
                'password' => $mailerPassword,
                'port' => '465',
                'encryption' => 'ssl',
            ],
        ],
    ],
    'params' => [
        'migratePasswordsFromLdap' => Env::get('MIGRATE_PW_FROM_LDAP', false),
    ],
];
