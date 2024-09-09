<?php

namespace console\controllers;

use common\components\ExternalGroupsSync;
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
        $eventCategory = 'mfa-usage';

        $gaEvents = [
            'active_users' => User::find()->where(['active' => 'yes'])->count(),
            'active_users_with_require_mfa' => User::countUsersWithRequireMfa(),
            'active_users_with_mfas' => User::getQueryOfUsersWithMfa()->count(),
            'active_users_with_backup_codes' => User::getQueryOfUsersWithMfa(Mfa::TYPE_BACKUPCODE)->count(),
            'active_users_with_totp' => User::getQueryOfUsersWithMfa(Mfa::TYPE_TOTP)->count(),
            'active_users_with_u2f' => User::getQueryOfUsersWithMfa(Mfa::TYPE_WEBAUTHN)->count(),
            'active_users_with_password' => User::countUsersWithPassword(),
            // Since GA doesn't accept event values as floats, multiply this by 10 and round it
            'average_mfas_per_user_with_mfas_times_ten' => round(User::getAverageNumberOfMfasPerUserWithMfas() * 10.0),
            'active_users_personal_email_no_methods' => User::numberWithPersonalEmailButNoMethods(),
            'active_users_only_2sv_or_u2f' => User::numberWithOneMfaNotBackupCodes()
        ];

        list($gaService, $gaRequest) = Utils::GoogleAnalyticsServiceAndRequest("cron");
        if ($gaService === null) {
            return;
        }

        foreach ($gaEvents as $label => $value) {
            $gaEvent = new BaseEvent($label);
            $gaEvent->setCategory($eventCategory)
                ->setLabel($label)
                ->setValue($value);

            $gaRequest->addEvent($gaEvent);
        }

        $debugResponse = $gaService->sendDebug($gaRequest);
        $gaMessages = $debugResponse->getValidationMessages();
        if (empty($gaMessages)) {
            $gaService->send($gaRequest);
        } else {
            \Yii::warning([
                'google-analytics' => "Aborting GA cron since the request was not accepted: " .
                    var_export($gaMessages, true)
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
     * Sync external groups from Google Sheets
     */
    public function actionSyncExternalGroups()
    {
        ExternalGroupsSync::syncAllSets(
            \Yii::$app->params['externalGroupsSyncSets'] ?? []
        );
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
