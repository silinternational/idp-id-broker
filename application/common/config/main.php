<?php

use common\components\Emailer;
use common\components\MfaBackendBackupcode;
use common\components\MfaBackendManager;
use common\components\MfaBackendTotp;
use common\components\MfaBackendU2f;
use common\components\Sheets;
use Sil\JsonLog\target\EmailServiceTarget;
use Sil\JsonLog\target\JsonStreamTarget;
use Sil\PhpEnv\Env;
use yii\db\Connection;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

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

$passwordProfileUrl = Env::get('PASSWORD_PROFILE_URL');

$logPrefix = function () {
    $request = Yii::$app->request;
    $prefixData = [
        'env' => YII_ENV,
    ];
    if ($request instanceof \yii\web\Request) {
        // Assumes format: Bearer consumer-module-name-32randomcharacters
        $prefixData['id'] = substr($request->headers['Authorization'], 7, 16) ?: 'unknown';
        $prefixData['ip'] = $request->getUserIP();
        $prefixData['method'] = $request->getMethod();
        $prefixData['url'] = $request->getUrl();
    } elseif ($request instanceof \yii\console\Request) {
        $prefixData['id'] = '(console)';
    }

    return Json::encode($prefixData);
};

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
                'passwordProfileUrl' => $passwordProfileUrl . '/#',
                'supportEmail' => Env::get('SUPPORT_EMAIL'),
                'supportName' => Env::get('SUPPORT_NAME', 'support'),
            ],
            
            'sendInviteEmails' => Env::get('SEND_INVITE_EMAILS', true),
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
            'sendMethodReminderEmails' => Env::get('SEND_METHOD_REMINDER_EMAILS', true),
            'sendMethodPurgedEmails' => Env::get('SEND_METHOD_PURGED_EMAILS', true),
            'sendPasswordExpiringEmails' => Env::get('SEND_PASSWORD_EXPIRING_EMAILS', true),
            'sendPasswordExpiredEmails' => Env::get('SEND_PASSWORD_EXPIRED_EMAILS', true),

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
            'subjectForMfaManager' => Env::get('SUBJECT_FOR_MFA_MANAGER'),
            'subjectForMfaManagerHelp' => Env::get('SUBJECT_FOR_MFA_MANAGER_HELP'),
            'subjectForMethodVerify' => Env::get('SUBJECT_FOR_METHOD_VERIFY'),
            'subjectForMethodReminder' => Env::get('SUBJECT_FOR_METHOD_REMINDER'),
            'subjectForMethodPurged' => Env::get('SUBJECT_FOR_METHOD_PURGED'),
            'subjectForPasswordExpiring' => Env::get('SUBJECT_FOR_PASSWORD_EXPIRING'),
            'subjectForPasswordExpired' => Env::get('SUBJECT_FOR_PASSWORD_EXPIRED'),

            'lostSecurityKeyEmailDays' => Env::get('LOST_SECURITY_KEY_EMAIL_DAYS', 62),
            'minimumBackupCodesBeforeNag' => Env::get('MINIMUM_BACKUP_CODES_BEFORE_NAG', 4),

            'emailRepeatDelayDays' => Env::get('EMAIL_REPEAT_DELAY_DAYS', 31),
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
        'manager' => ['class' => MfaBackendManager::class],
        // http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
        'log' => [
            'targets' => [
                [
                    'class' => JsonStreamTarget::class,
                    'url' => 'php://stdout',
                    'levels' => ['info'],
                    'logVars' => [],
                    'categories' => ['application'],
                    'prefix' => $logPrefix,
                    'exportInterval' => 1,
                ],
                [
                    'class' => JsonStreamTarget::class,
                    'url' => 'php://stderr',
                    'levels' => ['error', 'warning'],
                    'logVars' => [],
                    'prefix' => $logPrefix,
                    'exportInterval' => 1,
                ],
                [
                    'class' => EmailServiceTarget::class,
                    'categories' => ['application'], // only messages from this app, not all of Yii's built-in messaging
                    'enabled' => ! empty($notificationEmail),
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
                        'subject' => 'ERROR - ' . $idpName . ' ID Broker [' . YII_ENV . ']',
                    ],
                    'baseUrl' => $emailServiceConfig['baseUrl'],
                    'accessToken' => $emailServiceConfig['accessToken'],
                    'assertValidIp' => $emailServiceConfig['assertValidIp'],
                    'validIpRanges' => $emailServiceConfig['validIpRanges'],
                    'prefix' => $logPrefix,
                    'exportInterval' => 1,
                ],
            ],
        ],
    ],
    'params' => [
        'authorizedTokens'              => Env::getArray('API_ACCESS_KEYS'),
        'idpName'                       => $idpName,
        'idpDisplayName'                => $idpDisplayName,
        'mfaAddInterval'                => Env::get('MFA_ADD_INTERVAL', '+30 days'),
        'mfaRequiredForNewUsers'        => Env::get('MFA_REQUIRED_FOR_NEW_USERS', false),
        'mfaAllowDisable'               => Env::get('MFA_ALLOW_DISABLE', true),
        'methodAddInterval'             => Env::get('METHOD_ADD_INTERVAL', '+6 months'),
        'profileReviewInterval'         => Env::get('PROFILE_REVIEW_INTERVAL', '+6 months'),
        'passwordReuseLimit'            => Env::get('PASSWORD_REUSE_LIMIT', 10),
        'passwordLifespan'              => Env::get('PASSWORD_LIFESPAN', '+1 year'),
        'passwordMfaLifespanExtension'  => Env::get('PASSWORD_MFA_LIFESPAN_EXTENSION', '+4 years'),
        'passwordExpirationGracePeriod' => Env::get('PASSWORD_EXPIRATION_GRACE_PERIOD', '+30 days'),
        'passwordGracePeriodExtension'  => '+7 days',
        'inviteLifespan'                => Env::get('INVITE_LIFESPAN', '+1 month'),
        'inviteGracePeriod'             => Env::get('INVITE_GRACE_PERIOD', '+3 months'),
        'inactiveUserDeletionEnable'    => Env::get('INACTIVE_USER_DELETION_ENABLE', false),
        'inactiveUserPeriod'            => Env::get('INACTIVE_USER_PERIOD', '+18 months'),
        'googleAnalytics'               => [
            'trackingId' => Env::get('GA_TRACKING_ID'),
            'clientId'   => Env::get('GA_CLIENT_ID'),
        ],
        'method'                        => ArrayHelper::merge(
            [
                'lifetime' => '+5 days',
                'gracePeriod' => '+15 days',
                'codeLength' => 6,
                'maxAttempts' => 10,
            ],
            Env::getArrayFromPrefix('METHOD_')
        ),
        'mfaLifetime'                   => Env::get('MFA_LIFETIME', '+2 hours'),
        'mfaManagerBcc'                 => Env::get('MFA_MANAGER_BCC', ''),
        'mfaManagerHelpBcc'             => Env::get('MFA_MANAGER_HELP_BCC', ''),
        'contingentUserDuration'        => Env::get('CONTINGENT_USER_DURATION', '+4 weeks'),
        'inviteEmailDelaySeconds'       => Env::get('INVITE_EMAIL_DELAY_SECONDS', 0),
        'hibpCheckOnLogin'              => Env::get('HIBP_CHECK_ON_LOGIN', true),
        'hibpCheckInterval'             => Env::get('HIBP_CHECK_INTERVAL', '+1 week'),
        'hibpGracePeriod'               => Env::get('HIBP_GRACE_PERIOD', '+1 week'),
        'hibpTrackingOnly'              => Env::get('HIBP_TRACKING_ONLY', false),
        'hibpNotificationBcc'           => Env::get('HIBP_NOTIFICATION_BCC', ''),
        'google' => ArrayHelper::merge(
            [
            'applicationName'     => 'id-broker',
            'jsonAuthFilePath'    => '',
            'jsonAuthString'      => '',
            'delegatedAdmin'     => '',
            'spreadsheetId'       => '',
            ],
            Env::getArrayFromPrefix('GOOGLE_')
        ),
    ],
];
