<?php
namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use common\helpers\MySqlDateTime;
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
        $matchingFakeEmails = $this->fakeEmailer->getFakeEmailsOfTypeSentToUser(
            $messageType,
            $this->tempUser
        );
        Assert::greaterThan(count($matchingFakeEmails), 0, sprintf(
            'Did not find any %s emails sent to that user.',
            $messageType
        ));
    }

    /**
     * @Then a(n) :messageType email should NOT have been sent to them
     */
    public function aEmailShouldNotHaveBeenSentToThem($messageType)
    {
        $matchingFakeEmails = $this->fakeEmailer->getFakeEmailsOfTypeSentToUser(
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
     * @When I create a new user
     */
    public function iCreateANewUser()
    {
        $this->tempUser = $this->createNewUser();
    }
    
    protected function createNewUser()
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
        return $user;
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
     * @Given we are configured to send password-changed emails
     */
    public function weAreConfiguredToSendPasswordChangedEmails()
    {
        $this->fakeEmailer->sendPasswordChangedEmails = true;
    }

    /**
     * @Given we are NOT configured to send invite emails
     */
    public function weAreNotConfiguredToSendInviteEmails()
    {
        $this->fakeEmailer->sendInviteEmails = false;
    }

    /**
     * @Given we are NOT configured to send password-changed emails
     */
    public function weAreNotConfiguredToSendPasswordChangedEmails()
    {
        $this->fakeEmailer->sendPasswordChangedEmails = false;
    }

    /**
     * @Given a (specific) user already exists
     */
    public function aUserAlreadyExists()
    {
        $this->tempUser = $this->createNewUser();
    }

    /**
     * @Given that user does NOT have a password
     */
    public function thatUserDoesNotHaveAPassword()
    {
        if ($this->tempUser !== null) {
            Assert::null(
                $this->tempUser->current_password_id,
                'The user already has a password, but this test needs a user '
                . 'without a password.'
            );
        }
    }

    /**
     * @When I give that user a password
     */
    public function iGiveThatUserAPassword()
    {
        $this->setPasswordForUser(
            $this->tempUser,
            base64_encode(random_bytes(33)) // Random password
        );
    }
    
    protected function setPasswordForUser(User $user, string $newPassword)
    {
        $oldScenario = $user->scenario;
        $user->scenario = User::SCENARIO_UPDATE_PASSWORD;
        $user->password = $newPassword;
        $savedPassword = $user->save();
        Assert::true($savedPassword, 'Failed to give user a password.');
        $user->scenario = $oldScenario;
    }

    /**
     * @Given that user DOES have a password
     */
    public function thatUserDoesHaveAPassword()
    {
        $this->setPasswordForUser(
            $this->tempUser,
            base64_encode(random_bytes(33)) // Random password
        );
        $this->tempUser->refresh();
        Assert::notNull($this->tempUser->current_password_id);
    }

    /**
     * @When I save changes to that user without changing the password
     */
    public function iSaveChangesToThatUserWithoutChangingThePassword()
    {
        $this->tempUser->first_name .= ', changed ' . microtime();
        Assert::true(
            $this->tempUser->save(),
            var_export($this->tempUser->getFirstErrors(), true)
        );
    }

    /**
     * @Given I remove records of any emails that have been sent
     */
    public function iRemoveRecordsOfAnyEmailsThatHaveBeenSent()
    {
        $this->fakeEmailer->forgetFakeEmailsSent();
        EmailLog::deleteAll();
    }

    /**
     * @When I change that user's password
     */
    public function iChangeThatUsersPassword()
    {
        $this->iGiveThatUserAPassword();
    }

    /**
     * @Given a specific user does NOT exist
     */
    public function aSpecificUserDoesNotExist()
    {
        $this->tempUser = null;
    }

    /**
     * @When that user is created
     */
    public function thatUserIsCreated()
    {
        Assert::null($this->tempUser, 'The user should not have existed yet.');
        $this->iCreateANewUser();
    }

    /**
     * @When that user gets a password
     */
    public function thatUserGetsAPassword()
    {
        $this->iGiveThatUserAPassword();
    }

    /**
     * @When that user has non-pw changes
     */
    public function thatUserHasNonPwChanges()
    {
        $this->iSaveChangesToThatUserWithoutChangingThePassword();
    }

    /**
     * @Given that user has a password
     */
    public function thatUserHasAPassword()
    {
        $this->thatUserDoesHaveAPassword();
    }

    /**
     * @Given we are configured NOT to send password-changed emails
     */
    public function weAreConfiguredNotToSendPasswordChangedEmails()
    {
        $this->weAreNotConfiguredToSendPasswordChangedEmails();
    }

    /**
     * @Given we are configured NOT to send invite emails
     */
    public function weAreConfiguredNotToSendInviteEmails()
    {
        $this->weAreNotConfiguredToSendInviteEmails();
    }
}
