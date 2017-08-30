<?php
namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use common\models\EmailLog;
use common\models\User;
use Sil\SilIdBroker\Behat\Context\YiiContext;
use Webmozart\Assert\Assert;

class EmailContext extends YiiContext
{
    /** @var User */
    protected $tempUser;
    
    /**
     * @Then a(n) :messageType email should have been sent to them
     */
    public function aEmailShouldHaveBeenSentToThem($messageType)
    {
        $matchingFakeEmails = $this->getFakeEmailsOfTypeSentToUser(
            $messageType,
            $this->tempUser
        );
        Assert::greaterThan(count($matchingFakeEmails), 0);
    }

    /**
     * @Then a(n) :messageType email should NOT have been sent to them
     */
    public function aEmailShouldNotHaveBeenSentToThem($messageType)
    {
        $matchingFakeEmails = $this->getFakeEmailsOfTypeSentToUser(
            $messageType,
            $this->tempUser
        );
        Assert::isEmpty($matchingFakeEmails);
    }

    /**
     * @Then a(n) :messageType email to that user should NOT have been logged
     */
    public function aEmailToThatUserShouldNotHaveBeenLogged($messageType)
    {
        $emailLogs = EmailLog::findAll([
            'message_type' => $messageType,
            'user_id' => $this->tempUser->id,
        ]);
        Assert::isEmpty($emailLogs, sprintf(
            'Expected NOT to find any email logs for a(n) %s email to User %s, '
            . 'but instead found %s of them.',
            var_export($messageType, true),
            var_export($this->tempUser->id, true),
            count($emailLogs)
        ));
        Assert::false(
            $this->tempUser->hasReceivedMessage($messageType),
            'User::hasReceivedMessage() unexpectedly returned true.'
        );
    }
    
    /**
     * Get the actual email data (from our FakeEmailer) of any emails sent to
     * the given user and of the specified type.
     *
     * @param string $messageType The type of message.
     * @param User $user The User in question.
     * @return array[]
     */
    protected function getFakeEmailsOfTypeSentToUser(
        string $messageType,
        User $user
    ) {
        $fakeEmailer = $this->fakeEmailer;
        $fakeEmailsSent = $fakeEmailer->getFakeEmailsSent();
        
        return array_filter(
            $fakeEmailsSent,
            function ($fakeEmail) use ($fakeEmailer, $messageType, $user) {
                
                $subject = $fakeEmail['subject'] ?? '';
                $toAddress = $fakeEmail['to_address'] ?? '';
                
                return $fakeEmailer->isSubjectForMessageType($subject, $messageType)
                    && ($toAddress === $user->email);
            }
        );
    }
    
    /**
     * @When I create a new user
     */
    public function iCreateANewUser()
    {
        $employeeId = uniqid();
        $user = new User([
            'employee_id' => strval($employeeId),
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'test_user_' . $employeeId,
            'email' => 'test_user_' . $employeeId . '@example.com',
        ]);
        $user->scenario = User::SCENARIO_NEW_USER;
        if ( ! $user->save()) {
            throw new \Exception(
                \json_encode($user->getFirstErrors(), JSON_PRETTY_PRINT)
            );
        }
        $user->refresh();
        $this->tempUser = $user;
    }

    /**
     * @Then a(n) :messageType email to that user should have been logged
     */
    public function aEmailToThatUserShouldHaveBeenLogged($messageType)
    {
        $emailLogs = EmailLog::findAll([
            'message_type' => $messageType,
            'user_id' => $this->tempUser->id,
        ]);
        Assert::count($emailLogs, 1, sprintf(
            'Expected to find an email log for a(n) %s email to User %s, but '
            . 'instead found %s of them.',
            var_export($messageType, true),
            var_export($this->tempUser->id, true),
            count($emailLogs)
        ));
        Assert::true(
            $this->tempUser->hasReceivedMessage($messageType),
            'User::hasReceivedMessage() unexpectedly returned false.'
        );
    }
    
    /**
     * @Given we are configured to send invite emails
     */
    public function weAreConfiguredToSendInviteEmails()
    {
        $this->fakeEmailer->sendInviteEmails = true;
    }
    
    /**
     * @Given we are configured to send welcome emails
     */
    public function weAreConfiguredToSendWelcomeEmails()
    {
        $this->fakeEmailer->sendWelcomeEmails = true;
    }

    /**
     * @Given we are NOT configured to send invite emails
     */
    public function weAreNotConfiguredToSendInviteEmails()
    {
        $this->fakeEmailer->sendInviteEmails = false;
    }

    /**
     * @Given we are NOT configured to send welcome emails
     */
    public function weAreNotConfiguredToSendWelcomeEmails()
    {
        $this->fakeEmailer->sendWelcomeEmails = false;
    }
}
