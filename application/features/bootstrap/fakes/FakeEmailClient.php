<?php

namespace Sil\SilIdBroker\Behat\Context\fakes;

use common\components\EmailClient;

class FakeEmailClient extends EmailClient
{
    public $emailsSent = [];

    public function email(array $config = []): void
    {
        $this->emailsSent[] = $config;
    }
}
