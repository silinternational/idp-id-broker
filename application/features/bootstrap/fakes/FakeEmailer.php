<?php
namespace Sil\SilIdBroker\Behat\Context\fakes;

use common\components\Emailer;
use Sil\SilIdBroker\Behat\Context\fakes\FakeEmailServiceClient;

class FakeEmailer extends Emailer
{
    /**
     * @return FakeEmailServiceClient
     */
    protected function getEmailServiceClient()
    {
        if ($this->emailServiceClient === null) {
            
            $this->emailServiceClient = new FakeEmailServiceClient(
                $this->emailServiceConfig['baseUrl'],
                $this->emailServiceConfig['accessToken'],
                [
                    FakeEmailServiceClient::ASSERT_VALID_IP_CONFIG => $this->emailServiceConfig['assertValidIp'],
                    FakeEmailServiceClient::TRUSTED_IPS_CONFIG => $this->emailServiceConfig['validIpRanges'],
                ]
            );
        }
        
        return $this->emailServiceClient;
    }
    
    public function getFakeEmailsSent()
    {
        return $this->getEmailServiceClient()->emailsSent;
    }
    
    public function isSubjectForMessageType(string $subject, string $messageType)
    {
        return ($this->getSubjectForMessage($messageType) === $subject);
    }
}
