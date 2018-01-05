<?php
namespace console\controllers;

use common\models\Mfa;
use common\models\User;
use common\components\Emailer;
use yii\console\Controller;

use TheIconic\Tracking\GoogleAnalytics\Analytics;

class CronController extends Controller
{
    public function actionRemoveOldUnverifiedRecords()
    {
        \Yii::warning([
            'action' => 'delete old unverified mfa records',
            'status' => 'starting',
        ]);
        Mfa::removeOldUnverifiedRecords();
    }

    /**
     * Send events to Google Analytics that give the number of ...
     *  - active users
     *  - active users that have a verified Mfa of any type
     *  - active users with a backup code Mfa
     *  - active users with a verified totp Mfa
     *  - active users with a verified u2f Mfa
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
            'active-users-with-u2f' => User::getQueryOfUsersWithMfa(Mfa::TYPE_U2F)->count(),
            'active-users-with-password' => User::countUsersWithPassword(),
            // Since GA doesn't accept event values as floats, multiply this by 10 and round it
            'average-mfas-per-user-with-mfas-times-ten' => round(User::getAverageNumberOfMfasPerUserWithMfas() * 10.0),
        ];

        $analytics = new Analytics();
        $analytics->setProtocolVersion('1')
            ->setTrackingId($trackingId)
            ->setClientId($clientId)
            ->setEventCategory($eventCategory);

        foreach ($gaEvents as $label => $value) {
            $analytics->setEventLabel($label)
                ->setEventValue($value)
                ->sendEvent();
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
}