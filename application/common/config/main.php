<?php

use common\components\Emailer;
use common\components\MfaBackendBackupcode;
use common\components\MfaBackendTotp;
use common\components\MfaBackendU2f;
use common\ldap\Ldap;
use Sil\JsonLog\target\JsonSyslogTarget;
use Sil\JsonLog\target\EmailServiceTarget;
use Sil\PhpEnv\Env;
use Sil\Psr3Adapters\Psr3Yii2Logger;
use yii\db\Connection;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\Request;

$idpName        = Env::requireEnv('IDP_NAME');
$idpDisplayName = Env::get('IDP_DISPLAY_NAME', $idpName);
$mysqlHost      = Env::requireEnv('MYSQL_HOST');
$mysqlDatabase  = Env::requireEnv('MYSQL_DATABASE');
$mysqlUser      = Env::requireEnv('MYSQL_USER');
$mysqlPassword  = Env::requireEnv('MYSQL_PASSWORD');

$notificationEmail = Env::get('NOTIFICATION_EMAIL');

$mfaNumBackupCodes = Env::get('MFA_NUM_BACKUPCODES', 10);

$mfaTotpConfig = Env::getArrayFromPrefix('MFA_TOTP_');
$mfaTotpConfig['issuer'] = $idpDisplayName;

$mfaU2fConfig = Env::getArrayFromPrefix('MFA_U2F_');

$emailerClass = Env::get('EMAILER_CLASS', Emailer::class);

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
            'class' => $emailerClass,
            'emailServiceConfig' => $emailServiceConfig,
            
            'otherDataForEmails' => [
                'emailSignature' => Env::get('EMAIL_SIGNATURE', ''),
                'helpCenterUrl' => Env::get('HELP_CENTER_URL'),
                'idpDisplayName' => $idpDisplayName,
                'passwordForgotUrl' => Env::get('PASSWORD_FORGOT_URL'),
                'passwordProfileUrl' => Env::get('PASSWORD_PROFILE_URL'),
                'supportEmail' => Env::get('SUPPORT_EMAIL'),
                'supportName' => Env::get('SUPPORT_NAME', 'support'),
            ],
            
            'sendInviteEmails' => Env::get('SEND_INVITE_EMAILS', false),
            'sendMfaRateLimitEmails' => Env::get('SEND_MFA_RATE_LIMIT_EMAILS', true),
            'sendPasswordChangedEmails' => Env::get('SEND_PASSWORD_CHANGED_EMAILS', true),
            'sendWelcomeEmails' => Env::get('SEND_WELCOME_EMAILS', true),
            // When they have no backup codes yet
            'sendGetBackupCodesEmails' => Env::get('SEND_GET_BACKUP_CODES_EMAILS', true),
            // When they are getting low on backup codes
            'sendRefreshBackupCodesEmails' => Env::get('SEND_REFRESH_BACKUP_CODES_EMAILS', true),
            'sendLostSecurityKeyEmails' => Env::get('SEND_LOST_SECURITY_KEY_EMAILS', true),
            'sendMfaOptionAddedEmails' => Env::get('SEND_MFA_OPTION_ADDED_EMAILS', true),
            'sendMfaOptionRemovedEmails' => Env::get('SEND_MFA_OPTION_REMOVED_EMAILS', true),
            'sendMfaEnabledEmails' => Env::get('SEND_MFA_ENABLED_EMAILS', true),
            'sendMfaDisabledEmails' => Env::get('SEND_MFA_DISABLED_EMAILS', true),

            'subjectForInvite' => Env::get('SUBJECT_FOR_INVITE'),
            'subjectForMfaRateLimit' => Env::get('SUBJECT_FOR_MFA_RATE_LIMIT'),
            'subjectForPasswordChanged' => Env::get('SUBJECT_FOR_PASSWORD_CHANGED'),
            'subjectForWelcome' => Env::get('SUBJECT_FOR_WELCOME'),
            'subjectForGetBackupCodes' => Env::get('SUBJECT_FOR_GET_BACKUP_CODES'),
            'subjectForRefreshBackupCodes' => Env::get('SUBJECT_FOR_REFRESH_BACKUP_CODES'),
            'subjectForLostSecurityKey' => Env::get('SUBJECT_FOR_LOST_SECURITY_KEY'),
            'subjectForMfaOptionAdded' => Env::get('SUBJECT_FOR_MFA_OPTION_ADDED'),
            'subjectForMfaOptionRemoved' => Env::get('SUBJECT_FOR_MFA_OPTION_REMOVED'),
            'subjectForMfaEnabled' => Env::get('SUBJECT_FOR_MFA_ENABLED'),
            'subjectForMfaDisabled' => Env::get('SUBJECT_FOR_MFA_DISABLED'),

            'lostSecurityKeyEmailDays' => Env::get('LOST_SECURITY_KEY_EMAIL_DAYS', 62),
            'minimumBackupCodesBeforeNag' => Env::get('MINIMUM_BACKUP_CODES_BEFORE_NAG', 4),

            'emailRepeatDelayDays' => Env::get('EMAIL_REPEAT_DELAY_DAYS', 31),
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
        'backupcode' => [
            'class' => MfaBackendBackupcode::class,
            'numBackupCodes' => $mfaNumBackupCodes,
        ],
        'totp' => ArrayHelper::merge(
            ['class' => MfaBackendTotp::class],
            $mfaTotpConfig
        ),
        'u2f' => ArrayHelper::merge(
            ['class' => MfaBackendU2f::class],
            $mfaU2fConfig
        ),
        // http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
        'log' => [
            'targets' => [
                [
                    'class' => JsonSyslogTarget::class,
                    'categories' => ['application'], // stick to messages from this app, not all of Yii's built-in messaging.
                    'logVars' => [], // no need for default stuff: http://www.yiiframework.com/doc-2.0/yii-log-target.html#$logVars-detail
                    'prefix' => function () {
                        $request = Yii::$app->request;
                        $prefixData = [
                            'env' => YII_ENV,
                        ];
                        
                        if ($request instanceof \yii\web\Request) {
                            // Assumes format: Bearer consumer-module-name-32randomcharacters
                            $prefixData['id'] = substr($request->headers['Authorization'], 7, 16) ?: 'unknown';
                            $prefixData['ip'] = $request->getUserIP();
                        } elseif ($request instanceof \yii\console\Request) {
                            $prefixData['id'] = '(console)';
                        }
                        
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
                        'to' => $notificationEmail ?? '(disabled)',
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
        'authorizedTokens'              => Env::getArray('API_ACCESS_KEYS'),
        'idpName'                       => $idpName,
        'idpDisplayName'                => $idpDisplayName,
        'mfaNagInterval'                => Env::get('MFA_NAG_INTERVAL', '+30 days'),
        'migratePasswordsFromLdap'      => Env::get('MIGRATE_PW_FROM_LDAP', false),
        'passwordReuseLimit'            => Env::get('PASSWORD_REUSE_LIMIT', 10),
        'passwordLifespan'              => Env::get('PASSWORD_LIFESPAN', '+1 year'),
        'passwordMfaLifespanExtension'  => Env::get('PASSWORD_MFA_LIFESPAN_EXTENSION', '+1 year'),
        'passwordExpirationGracePeriod' => Env::get('PASSWORD_EXPIRATION_GRACE_PERIOD', '+30 days'),
        'googleAnalytics'               => [
            'trackingId' => Env::get('GA_TRACKING_ID'),
            'clientId'   => Env::get('GA_CLIENT_ID'),
        ]
    ],
];
