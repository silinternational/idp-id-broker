<?php
namespace console\controllers;

use common\helpers\Utils;
use yii\console\Controller;

use Br33f\Ga4\MeasurementProtocol\Dto\Event\BaseEvent;

class Ga4Controller extends Controller
{

    /**
     * Send test event to Google Analytics 4
     * Call it with this command
     * $ ./yii ga4/register_event
     */
    public function actionRegister_event()
    {

        list($ga4Service, $ga4Request) = Utils::GoogleAnalyticsServiceAndRequest("cron");
        if ($ga4Service === null) {
            return;
        }

        $ga4Event = new BaseEvent("id_broker_test_name");
        $ga4Event->setCategory("id_broker_test")
            ->setLabel("id_broker_test_label")
            ->setValue("id_broker_test_value");

        $ga4Request->addEvent($ga4Event);

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

        $ga4Id = \Yii::$app->params['googleAnalytics4']['measurementId'];

        print_r(PHP_EOL .
            "Now go to Google Analytics data stream $ga4Id, to the reports:realtime page " .
            " and make sure the events are appearing in the " .
            "'Event count by Event name' widget." . PHP_EOL);
    }

}
