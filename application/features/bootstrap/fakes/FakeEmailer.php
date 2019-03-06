<?php
namespace Sil\SilIdBroker\Behat\Context\fakes;

use common\components\Emailer;
use common\models\User;
use Sil\SilIdBroker\Behat\Context\fakes\FakeEmailServiceClient;
use yii\helpers\ArrayHelper;

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
     * @param string $emailAddress Email address to find.
     * @param User $user User record for subject text completion.
     * @return array[]
     */
    public function getFakeEmailsOfTypeSentToUser(string $messageType, string $emailAddress, User $user)
    {
        $fakeEmailer = $this;
        $fakeEmailsSent = $fakeEmailer->getFakeEmailsSent();

        return array_filter(
            $fakeEmailsSent,
            function ($fakeEmail) use ($fakeEmailer, $messageType, $emailAddress, $user) {
                
                $subject = $fakeEmail[Emailer::PROP_SUBJECT] ?? '';
                $toAddress = $fakeEmail[Emailer::PROP_TO_ADDRESS] ?? '';
                
                return $fakeEmailer->isSubjectForMessageType($subject, $messageType, $user)
                    && ($toAddress === $emailAddress);
            }
        );
    }

    public function getFakeEmailsSent()
    {
        return $this->getEmailServiceClient()->emailsSent;
    }
    
    public function isSubjectForMessageType(string $subject, string $messageType, User $user)
    {
        $dataForEmail = ArrayHelper::merge(
            $user->getAttributesForEmail(),
            $this->otherDataForEmails
        );

        return ($this->getSubjectForMessage($messageType, $dataForEmail) === $subject);
    }
}
