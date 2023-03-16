<?php
namespace console\controllers;

use common\helpers\Utils;
use common\models\Invite;
use common\models\Method;
use common\models\Mfa;
use common\models\User;
use common\components\Emailer;
use yii\console\Controller;

use Br33f\Ga4\MeasurementProtocol\Dto\Event\BaseEvent;
use TheIconic\Tracking\GoogleAnalytics\Analytics;

class CronController extends Controller
{
    public function actionRemoveOldUnverifiedRecords()
    {
        Method::deleteExpiredUnverifiedMethods();

        Invite::deleteOldInvites();

        Mfa::removeOldUnverifiedRecords();

        Mfa::removeOldManagerMfaRecords();
    }

    /**
     * Send events to Google Analytics that give the number of ...
     *  - active users
     *  - active users that have a verified Mfa of any type
     *  - active users with a backup code Mfa
     *  - active users with a verified totp Mfa
     *  - active users with a verified u2f/webauthn Mfa
     *
     * If you need to debug the Google Analytics call, do this ...
     *     $response = $analytics->setProtocolVersion('1')
     *                           ->setDebug(true)
     *     ...
     *     ...
     *     \Yii::warning([
     *            'results' => $response->getDebugResponse(),
     *     ]);
     *
     *
     * @throws \Exception
     */
    public function actionGoogleAnalytics()
    {
        $trackingId = \Yii::$app->params['googleAnalytics']['trackingId']; // 'UA-12345678-12'
        if ($trackingId === null) {
            \Yii::warning(['google-analytics' => "Aborting GA cron, since the config has no GA trackingId"]);
            return;
        }

        $clientId = \Yii::$app->params['googleAnalytics']['clientId']; // 'IDP_ID_BROKER_LOCALHOST'
        if ($clientId === null) {
            \Yii::warning(['google-analytics' => "Aborting GA cron, since the config has no GA clientId"]);
            return;
        }

        $eventCategory = 'mfa-usage';

        $gaEvents = [
            'active-users' => User::find()->where(['active' => 'yes'])->count(),
            'active-users-with-require-mfa' => User::countUsersWithRequireMfa(),
            'active-users-with-mfas' => User::getQueryOfUsersWithMfa()->count(),
            'active-users-with-backup-codes' => User::getQueryOfUsersWithMfa(Mfa::TYPE_BACKUPCODE)->count(),
            'active-users-with-totp' => User::getQueryOfUsersWithMfa(Mfa::TYPE_TOTP)->count(),
            'active-users-with-u2f' => User::getQueryOfUsersWithMfa(Mfa::TYPE_WEBAUTHN)->count(),
            'active-users-with-password' => User::countUsersWithPassword(),
            // Since GA doesn't accept event values as floats, multiply this by 10 and round it
            'average-mfas-per-user-with-mfas-times-ten' => round(User::getAverageNumberOfMfasPerUserWithMfas() * 10.0),
            'active-users-personal-email-no-methods' => User::numberWithPersonalEmailButNoMethods(),
            'active-users-only-2sv-or-u2f' => User::numberWithOneMfaNotBackupCodes()
        ];

        $analytics = new Analytics();
        $analytics->setProtocolVersion('1')
            ->setTrackingId($trackingId)
            ->setClientId($clientId)
            ->setEventCategory($eventCategory);

        // Now call Google Analytics 4
        list($ga4Service, $ga4Request) = Utils::GoogleAnalyticsServiceAndRequest("cron");
        if ($ga4Service === null) {
            return;
        }

        foreach ($gaEvents as $label => $value) {
            $analytics->setEventLabel($label)
                ->setEventValue($value)
                ->sendEvent();

            $ga4Event = new BaseEvent($label);
            $ga4Event->setCategory($eventCategory)
                ->setLabel($label)
                ->setValue($value);

            $ga4Request->addEvent($ga4Event);
        }

        $debugResponse = $ga4Service->sendDebug($ga4Request);
        $ga4Messages = $debugResponse->getValidationMessages();
        if (empty($ga4Messages)) {
            $ga4Service->send($ga4Request);
        } else {
            \Yii::warning([
                'google-analytics4' => "Aborting GA4 cron since the request was not accepted: " .
                    var_export($ga4Messages, true)
            ]);
            return;
        }

        $gaEvents['action'] = 'completed posting to Google Analytics';

        \Yii::warning($gaEvents);
    }

    public function actionSendDelayedMfaRelatedEmails()
    {
        /* @var $emailer Emailer */
        $emailer = \Yii::$app->emailer;
        $emailer->sendDelayedMfaRelatedEmails();
    }

    public function actionSendMethodReminderEmails()
    {
        /* @var $emailer Emailer */
        $emailer = \Yii::$app->emailer;
        $emailer->sendMethodReminderEmails();
    }

    public function actionSendPasswordExpiryEmails()
    {
        /* @var $emailer Emailer */
        $emailer = \Yii::$app->emailer;
        $emailer->sendPasswordExpiringEmails();
        $emailer->sendPasswordExpiredEmails();
    }

    public function actionDeleteInactiveUsers()
    {
        User::deleteInactiveUsers();
    }

    public function actionSendAbandonedUsersEmail()
    {
        /* @var $emailer Emailer */
        $emailer = \Yii::$app->emailer;
        $emailer->sendAbandonedUsersEmail();
    }

    /**
     * Export user table to Google Sheets
     */
    public function actionExportToSheets()
    {
        User::exportToSheets();
    }

    /**
     * Run all cron tasks, catching and logging errors to allow remaining tasks to run after an exception
     */
    public function actionAll()
    {
        $actions = [
            'actionRemoveOldUnverifiedRecords',
            'actionDeleteInactiveUsers',
            'actionSendAbandonedUsersEmail',
            'actionSendDelayedMfaRelatedEmails',
            'actionSendMethodReminderEmails',
            'actionSendPasswordExpiryEmails',
            'actionGoogleAnalytics',
        ];

        if (\Yii::$app->params['google']['enableSheetsExport']) {
            $actions[] = 'actionExportToSheets';
        }

        foreach ($actions as $action) {
            try {
                $this->$action();
            } catch (\Throwable $e) {
                \Yii::error($e->getMessage());
            }
        }
    }
}
