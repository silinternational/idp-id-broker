<?php

namespace console\controllers;

use common\components\Emailer;
use common\components\ExternalGroupsSync;
use common\models\Invite;
use common\models\Method;
use common\models\Mfa;
use common\models\User;
use yii\console\Controller;

class CronController extends Controller
{
    public function actionRemoveOldUnverifiedRecords()
    {
        Method::deleteExpiredUnverifiedMethods();

        Invite::deleteOldInvites();

        Mfa::removeOldUnverifiedRecords();

        Mfa::removeOldManagerMfaRecords();
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
            'actionSyncExternalGroups',
        ];

        if (\Yii::$app->params['google']['enableSheetsExport']) {
            $actions[] = 'actionExportToSheets';
        }
        
        $actions[] = 'actionSendPasswordExpiryEmails';

        foreach ($actions as $action) {
            try {
                $this->$action();
            } catch (\Throwable $e) {
                \Yii::error($e->getMessage());
            }
        }
    }
}
