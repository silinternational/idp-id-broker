<?php

namespace Sil\SilIdBroker\Behat\Context\fakes;

use yii\log\Target;

/**
 * A *FAKE* Log Target (for Yii2) that lets tests examine what was logged.
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
