<?php
namespace console\controllers;

use common\models\Mfa;
use common\models\User;
use yii\console\Controller;
use Sil\PhpEnv\Env;

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
     * @throws \Exception
     */
    public function actionGoogleAnalytics()
    {
        $trackingId = Env::get('GA_TRACKING_ID', null); // 'UA-12345678-12'
        if ($trackingId === null) {
            throw new \Exception('GA_TRACKING_ID is required');
        }

        $clientId = Env::get('GA_CLIENT_ID', null); // 'IDP_ID_BROKER_LOCALHOST'
        if ($clientId === null) {
            throw new \Exception('GA_CLIENT_ID is required');
        }

        $eventCategory = 'mfa-usage';

        $gaEvents = [
            'active-users' => User::find()->where(['active' => 'yes'])->count(),
            'active-users-with-mfas' => User::getQueryOfUsersWithMfa()->count(),
            'active-users-with-backup-codes' => User::getQueryOfUsersWithMfa(Mfa::TYPE_BACKUPCODE)->count(),
            'active-users-with-totp' => User::getQueryOfUsersWithMfa(Mfa::TYPE_TOTP)->count(),
            'active-users-with-u2f' => User::getQueryOfUsersWithMfa(Mfa::TYPE_U2F)->count(),
        ];

        $analytics = new Analytics();
//  For debugging Google Analytics, uncomment next two lines and the Yii::warning block below and comment out the first $analytics->setProtocolVersion line
//        $response = $analytics->setProtocolVersion('1')
//            ->setDebug(true)
        $analytics->setProtocolVersion('1')
            ->setTrackingId($trackingId)
            ->setClientId($clientId)
            ->setEventCategory($eventCategory);


        foreach ($gaEvents as $label => $value) {
            $analytics->setEventLabel($label)
                ->setEventValue($value)
                ->sendEvent();
        }

//        \Yii::warning([
//            'results' => $response->getDebugResponse(),
//        ]);

        $gaEvents['action'] = 'completed posting to Google Analytics';

        \Yii::warning($gaEvents);
    }
}