<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Context\Context;

class YiiContext implements Context
{
    /**
     * @BeforeSuite
     */
    public static function loadYiiApp() {
        require(__DIR__ . '/../../frontend/web/load-app.php');
    }
}
