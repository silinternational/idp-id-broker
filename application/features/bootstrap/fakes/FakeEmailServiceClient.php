<?php
namespace Sil\SilIdBroker\Behat\Context\fakes;

use Sil\EmailService\Client\EmailServiceClient;

class FakeEmailServiceClient extends EmailServiceClient
{
    public $emailsSent = [];
    
    public function email(array $config = [])
    {
        $this->emailsSent[] = $config;
        return $config;
    }
}
