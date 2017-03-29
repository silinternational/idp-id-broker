<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Context\Context;
use yii\web\Application;

class YiiContext implements Context
{
    /**
     * @BeforeSuite
     */
    public static function loadYiiApp() {
        $config = require(__DIR__ . '/../../frontend/config/load-configs.php');

        new Application($config);
    }
}
