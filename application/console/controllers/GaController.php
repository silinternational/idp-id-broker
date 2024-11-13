<?php

namespace console\controllers;

use common\helpers\Utils;
use yii\console\Controller;
use Br33f\Ga4\MeasurementProtocol\Dto\Event\BaseEvent;

class GaController extends Controller
{
    /**
     * Send test event to Google Analytics
     * Call it with this command
     * $ ./yii ga/register_event
     */
    public function actionRegister_event()
    {
        \Yii::warning(
            'Sending ID Broker data to Google Analytics is deprecated and will '
            . 'be removed in a future release.'
        );

        list($gaService, $gaRequest) = Utils::GoogleAnalyticsServiceAndRequest("cron");
        if ($gaService === null) {
            return;
        }

        $gaEvent = new BaseEvent("id_broker_test_name");
        $gaEvent->setCategory("id_broker_test")
            ->setLabel("id_broker_test_label")
            ->setValue("id_broker_test_value");

        $gaRequest->addEvent($gaEvent);

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

        $gaId = \Yii::$app->params['googleAnalytics']['measurementId'];

        print_r(
            PHP_EOL .
            "Now go to the Google Analytics data stream $gaId, " . PHP_EOL .
            "to the reports:realtime page " .
            " and make sure the events are appearing in the " . PHP_EOL .
            "'Event count by Event name' widget." . PHP_EOL .
            "  Note: The GA API fails silently if you use the wrong API secret." . PHP_EOL
        );
    }

}
