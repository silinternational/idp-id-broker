<?php
namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use common\models\EmailLog;
use common\models\User;
use Sil\SilIdBroker\Behat\Context\fakes\FakeEmailer;
use Webmozart\Assert\Assert;
use Yii;

class EmailContext implements Context
{
    /** @var FakeEmailer */
    protected $fakeEmailer;
    
    /** @var User */
    protected $tempUser;
    
    public function __construct()
    {
        $this->fakeEmailer = new FakeEmailer([
            'emailServiceConfig' => [
                'accessToken' => 'fake-token-123',
                'assertValidIp' => false,
                'baseUrl' => 'http://fake-url',
                'validIpRanges' => ['192.168.0.0/16'],
            ],
        ]);
    }

    /**
     * @Then a(n) :messageType email should have been sent to them
     */
    public function aEmailShouldHaveBeenSentToThem($messageType)
    {
        $fakeEmailer = $this->fakeEmailer;
        $foundIt = false;
        foreach ($fakeEmailer->getFakeEmailsSent() as $fakeEmail) {
            $subject = $fakeEmail['subject'] ?? '';
            if ($fakeEmailer->isSubjectForMessageType($subject, $messageType)) {
                $foundIt = true;
                break;
            }
        }
        return $foundIt;
    }
    
    protected function giveYiiFakeEmailer()
    {
        Yii::$app->set('emailer', $this->fakeEmailer);
    }

    /**
     * @When I create a new user
     */
    public function iCreateANewUser()
    {
        $this->giveYiiFakeEmailer();
        
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
}
