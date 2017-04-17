<?php

use Sil\JsonSyslog\JsonSyslogTarget;
use Sil\Log\EmailTarget;
use Sil\PhpEnv\Env;
use yii\db\Connection;
use yii\helpers\Json;
use yii\swiftmailer\Mailer;
use yii\web\Request;

$idpName = getRequiredEnv('IDP_NAME');

$mysqlHost     = getRequiredEnv('MYSQL_HOST');
$mysqlDatabase = getRequiredEnv('MYSQL_DATABASE');
$mysqlUser     = getRequiredEnv('MYSQL_USER');
$mysqlPassword = getRequiredEnv('MYSQL_PASSWORD');

$mailerUseFiles    = Env::get('MAILER_USEFILES', false);
$mailerHost        = Env::get('MAILER_HOST');
$mailerUsername    = Env::get('MAILER_USERNAME');
$mailerPassword    = Env::get('MAILER_PASSWORD');
$notificationEmail = Env::get('NOTIFICATION_EMAIL', 'oncall@example.org');

function getRequiredEnv($name)
{
    $value = Env::get($name);

    if (empty($value)) {
        Yii::error("$name missing from environment.");
    }

    return $value;
}

return [
    'id' => 'app-common',
    'bootstrap' => ['log'],
    'components' => [
        'db' => [
            'class' => Connection::class,
            'dsn' => sprintf('mysql:host=%s;dbname=%s', $mysqlHost, $mysqlDatabase),
            'username' => $mysqlUser,
            'password' => $mysqlPassword,
            'charset' => 'utf8',
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
                        'from' => $notificationEmail,
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
];
