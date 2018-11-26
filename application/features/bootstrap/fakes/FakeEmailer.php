<?php
namespace Sil\SilIdBroker\Behat\Context\fakes;

use common\components\Emailer;
use common\models\User;
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
    
    public function forgetFakeEmailsSent()
    {
        return $this->getEmailServiceClient()->emailsSent = [];
    }
    
    /**
     * Get the actual email data (from this FakeEmailer) of any emails sent to
     * the given user and of the specified type.
     *
     * @param string $messageType The type of message.
     * @param User $user The User in question.
     * @param string $address Email address to find. If provided, overrides $user->email.
     * @return array[]
     */
    public function getFakeEmailsOfTypeSentToUser(
        string $messageType,
        User $user,
        string $address = null
    ) {
        $fakeEmailer = $this;
        $fakeEmailsSent = $fakeEmailer->getFakeEmailsSent();

        $checkAddress = $address ?? $user->email;

        return array_filter(
            $fakeEmailsSent,
            function ($fakeEmail) use ($fakeEmailer, $messageType, $checkAddress) {
                
                $subject = $fakeEmail['subject'] ?? '';
                $toAddress = $fakeEmail['to_address'] ?? '';
                
                return $fakeEmailer->isSubjectForMessageType($subject, $messageType)
                    && ($toAddress === $checkAddress);
            }
        );
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
