<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Context\Context;
use Sil\Psr3Adapters\Psr3ConsoleLogger;
use Sil\SilIdBroker\Behat\Context\fakes\FakeEmailer;
use Sil\SilIdBroker\Behat\Context\fakes\FakeLogTarget;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Application;

class YiiContext implements Context
{
    /** @var FakeEmailer */
    protected $fakeEmailer;

    /** @var FakeLogTarget */
    protected $fakeLogTarget;

    private static $application;

    public function __construct()
    {
        $yiiCommonConfig = require __DIR__ . '/../../common/config/main.php';
        $yiiEmailerConfig = $yiiCommonConfig['components']['emailer'];
        unset($yiiEmailerConfig['class']);

        $this->fakeEmailer = new FakeEmailer(ArrayHelper::merge(
            $yiiEmailerConfig,
            [
                'logger' => new Psr3ConsoleLogger(),
            ]
        ));
        $this->fakeEmailer->emailRepeatDelayDays = 31;
        $this->fakeEmailer->minimumBackupCodesBeforeNag = 4;

        Yii::$app->set('emailer', $this->fakeEmailer);

        $this->addFakeLogTarget();
    }

    protected function addFakeLogTarget()
    {
        $this->fakeLogTarget = new FakeLogTarget([
            'categories' => ['application'], // stick to messages from this app, not all of Yii's built-in messaging.
            'logVars' => [], // no need for default stuff: http://www.yiiframework.com/doc-2.0/yii-log-target.html#$logVars-detail
        ]);
        Yii::$app->log->targets[] = $this->fakeLogTarget;
    }

    /**
     * @BeforeSuite
     */
    public static function loadYiiApp()
    {
        if (empty(self::$application)) {
            $config = require(__DIR__ . '/../../frontend/config/load-configs.php');

            self::$application = new Application($config);
        }
    }
}
