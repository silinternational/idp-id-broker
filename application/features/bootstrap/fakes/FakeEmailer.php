<?php

namespace Sil\SilIdBroker\Behat\Context\fakes;

use common\components\Emailer;
use common\models\User;
use yii\helpers\ArrayHelper;

class FakeEmailer extends Emailer
{
    /**
     * @return FakeEmailClient
     */
    protected function getEmailClient()
    {
        if ($this->emailClient === null) {
            $this->emailClient = new FakeEmailClient();
        }

        return $this->emailClient;
    }

    public function forgetFakeEmailsSent()
    {
        return $this->getEmailClient()->emailsSent = [];
    }

    /**
     * Get the actual email data (from this FakeEmailer) of any emails sent to
     * the given user and of the specified type.
     *
     * @param string $messageType The type of message.
     * @param string $emailAddress Email address to find.
     * @param ?User $user User record for subject text completion, if applicable.
     * @return array[]
     */
    public function getFakeEmailsOfTypeSentToUser(string $messageType, string $emailAddress, ?User $user = null)
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
        return $this->getEmailClient()->emailsSent;
    }

    public function isSubjectForMessageType(
        string $subject,
        string $messageType,
        ?User $user = null
    ): bool {
        $dataForEmail = ArrayHelper::merge(
            $user ? $user->getAttributesForEmail() : [],
            $this->otherDataForEmails
        );

        return ($this->getSubjectForMessage($messageType, $dataForEmail) === $subject);
    }
}
