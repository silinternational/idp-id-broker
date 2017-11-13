<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Context\Context;
use Sil\SilIdBroker\Behat\Context\fakes\FakeEmailer;
use Sil\SilIdBroker\Behat\Context\fakes\FakeLogTarget;
use Yii;
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
        $this->fakeEmailer = new FakeEmailer([
            'emailServiceConfig' => [
                'accessToken' => 'fake-token-123',
                'assertValidIp' => false,
                'baseUrl' => 'http://fake-url',
                'validIpRanges' => ['192.168.0.0/16'],
            ],
        ]);
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
