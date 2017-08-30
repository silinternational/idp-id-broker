<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Context\Context;
use Sil\SilIdBroker\Behat\Context\fakes\FakeEmailer;
use Yii;
use yii\web\Application;

class YiiContext implements Context
{
    /** @var FakeEmailer */
    protected $fakeEmailer;
    
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
