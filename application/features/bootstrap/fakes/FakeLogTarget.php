<?php
namespace Sil\SilIdBroker\Behat\Context\fakes;

use yii\log\Target;

/**
 * A *FAKE* Log Target (for Yii2) that merely collects logs sent to it so that
 * tests can later confirm what was logged.
 */
class FakeLogTarget extends Target
{
    public function export()
    {
        // No op
    }
    
    public function getLoggedMessagesJson()
    {
        return \json_encode($this->messages, JSON_PRETTY_PRINT);
    }
}
