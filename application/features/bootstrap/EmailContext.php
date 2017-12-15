<?php
namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use common\helpers\MySqlDateTime;
use common\models\EmailLog;
use common\models\Mfa;
use common\models\MfaBackupcode;
use common\models\User;
use Sil\SilIdBroker\Behat\Context\YiiContext;
use Webmozart\Assert\Assert;

class EmailContext extends YiiContext
{
    /** @var User */
    protected $tempUser;

    /** @var bool */
    protected $lostKeyEmailShouldBeSent;

    /** @var bool */
    protected $getBackupCodesEmailShouldBeSent;

    /** @var bool */
    protected $refreshBackupCodesEmailShouldBeSent;

    /** @var bool */
    protected $mfaOptionAddedEmailShouldBeSent;

    /** @var bool */
    protected $mfaEnabledEmailShouldBeSent;

    /** @var bool */
    protected $mfaOptionRemovedEmailShouldBeSent;

    /** @var bool */
    protected $mfaDisabledEmailShouldBeSent;

    /** @var string */
    protected $mfaEventType;

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
     * @When that user is created
     */
    public function thatUserIsCreated()
    {
        Assert::null($this->tempUser, 'The user should not have existed yet.');
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

    protected function createMfa($type, $lastUsedDaysAgo=null)
    {
        $mfa = new Mfa();
        $mfa->user_id = $this->tempUser->id;
        $mfa->type = $type;
        $mfa->verified = 1;

        if ($lastUsedDaysAgo !== null) {
            $diffConfig = "-" . $lastUsedDaysAgo . " days";
            $mfa->last_used_utc= MySqlDateTime::relative($diffConfig);
        }
        assert::true($mfa->save(), "Could not create new mfa.");
        $this->tempUser->refresh();
    }

    protected function deleteMfaOfType($type) {
        foreach ($this->tempUser->mfas as $mfaOption) {
            if ($mfaOption->type === $type) {
                assert::true($mfaOption->delete(), 'Could not delete the ' . $type . ' mfa option for the test user.');
            }
        }
    }

    protected function getMfa($type)
    {
        $mfaOptions = $this->tempUser->getVerifiedMfaOptions();

        foreach ($mfaOptions as $mfa) {
            if ($mfa->type === $type) {
                return $mfa;
            }
        }

        return null;
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
     * @Given we are configured NOT to send invite emails
     */
    public function weAreConfiguredNotToSendInviteEmails()
    {
        $this->fakeEmailer->sendInviteEmails = false;
    }

    /**
     * @Given we are configured NOT to send password-changed emails
     */
    public function weAreConfiguredNotToSendPasswordChangedEmails()
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
     * @When that user gets a password
     */
    public function thatUserGetsAPassword()
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
     * @Given that user has a password
     */
    public function thatUserHasAPassword()
    {
        $this->setPasswordForUser(
            $this->tempUser,
            base64_encode(random_bytes(33)) // Random password
        );
        $this->tempUser->refresh();
        Assert::notNull($this->tempUser->current_password_id);
    }

    /**
     * @When that user has non-pw changes
     */
    public function thatUserHasNonPwChanges()
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
     * @Given we are configured to send mfa option added emails
     */
    public function weAreConfiguredToSendMfaOptionAddedEmails()
    {
        $this->fakeEmailer->sendMfaOptionAddedEmails = true;
    }

    /**
     * @Given we are configured NOT to send mfa option added emails
     */
    public function weAreConfiguredNotToSendMfaOptionAddedEmails()
    {
        $this->fakeEmailer->sendMfaOptionAddedEmails = false;
    }

    /**
     * @Given we are configured to send mfa enabled emails
     */
    public function weAreConfiguredToSendMfaEnabledEmails()
    {
        $this->fakeEmailer->sendMfaEnabledEmails = true;
    }

    /**
     * @Given we are configured NOT to send mfa enabled emails
     */
    public function weAreConfiguredNotToSendMfaEnabledEmails()
    {
        $this->fakeEmailer->sendMfaEnabledEmails = false;
    }

    /**
     * @Given we are configured to send mfa option removed emails
     */
    public function weAreConfiguredToSendMfaOptionRemovedEmails()
    {
        $this->fakeEmailer->sendMfaOptionRemovedEmails = true;
    }

    /**
     * @Given we are configured NOT to send mfa option removed emails
     */
    public function weAreConfiguredNotToSendMfaOptionRemovedEmails()
    {
        $this->fakeEmailer->sendMfaOptionRemovedEmails = false;
    }

    /**
     * @Given we are configured to send mfa disabled emails
     */
    public function weAreConfiguredToSendMfaDisabledEmails()
    {
        $this->fakeEmailer->sendMfaDisabledEmails = true;
    }

    /**
     * @Given we are configured NOT to send mfa disabled emails
     */
    public function weAreConfiguredNotToSendMfaDisabledEmails()
    {
        $this->fakeEmailer->sendMfaDisabledEmails = false;
    }

    /**
     * @Given we are configured to send welcome emails
     */
    public function weAreConfiguredToSendWelcomeEmails()
    {
        $this->fakeEmailer->sendWelcomeEmails = true;
    }

    /**
     * @Given we are configured NOT to send welcome emails
     */
    public function weAreConfiguredNotToSendWelcomeEmails()
    {
        $this->fakeEmailer->sendWelcomeEmails = false;
    }

    /**
     * @Given we are configured to send lost key emails
     */
    public function weAreConfiguredToSendLostKeyEmails()
    {
        $this->fakeEmailer->sendLostSecurityKeyEmails = true;
    }

    /**
     * @Given we are configured NOT to send lost key emails
     */
    public function weAreConfiguredNotToSendLostKeyEmails()
    {
        $this->fakeEmailer->sendLostSecurityKeyEmails = false;
    }

    /**
     * @Given we are configured to send get backup codes emails
     */
    public function weAreConfiguredToSendGetBackupCodesEmails()
    {
        $this->fakeEmailer->sendGetBackupCodesEmails = true;
    }

    /**
     * @Given we are configured NOT to send get backup codes emails
     */
    public function weAreConfiguredNotToSendGetBackupCodesEmails()
    {
        $this->fakeEmailer->sendGetBackupCodesEmails = false;
    }

    /**
     * @Given we are configured to send refresh backup codes emails
     */
    public function weAreConfiguredToSendRefreshBackupCodesEmails()
    {
        $this->fakeEmailer->sendRefreshBackupCodesEmails = true;
    }

    /**
     * @Given we are configured NOT to send refresh backup codes emails
     */
    public function weAreConfiguredNotToSendRefreshBackupCodesEmails()
    {
        $this->fakeEmailer->sendRefreshBackupCodesEmails = false;
    }

    /**
     * @Given no mfas exist
     */
    public function noMfasExist()
    {
        MfaBackupcode::deleteAll();
        Mfa::deleteAll();
    }

    /**
     * @Given a u2f mfa option does Exist
     */
    public function aU2fMfaOptionDoesExist()
    {
        $this->createMfa(Mfa::TYPE_U2F);
    }

    /**
     * @Given a u2f mfa option does NOT Exist
     */
    public function aU2fMfaOptionDoesNotExist()
    {
        $this->deleteMfaOfType(Mfa::TYPE_U2F);
    }

    /**
     * @Given a u2f mfa option was used :arg1 days ago
     */
    public function aU2fMfaOptionWasXUsedDaysAgo($lastUsedDaysAgo)
    {
        $this->createMfa(Mfa::TYPE_U2F, $lastUsedDaysAgo);
    }

    /**
     * @Given a totp mfa option does exist
     */
    public function aTotpMfaOptionDoesExist()
    {
        $this->createMfa(Mfa::TYPE_TOTP);
    }

    /**
     * @Given a totp mfa option does NOT exist
     */
    public function aTotpMfaOptionDoesNotExist()
    {
        $this->deleteMfaOfType(Mfa::TYPE_TOTP);
    }

    /**
     * @Given a totp mfa option was used :arg1 days ago
     */
    public function aTotpMfaOptionWasUsedDaysAgo($lastUsedDaysAgo)
    {
        $this->createMfa(Mfa::TYPE_TOTP, $lastUsedDaysAgo);
    }

    /**
     * @Given a backup code mfa option does exist
     */
    public function aBackupCodeMfaOptionDoesExist()
    {
        $this->createMfa(Mfa::TYPE_BACKUPCODE);
    }

    /**
     * @Given a backup code mfa option does NOT exist
     */
    public function aBackupCodeMfaOptionDoesNotExist()
    {
        $this->deleteMfaOfType(Mfa::TYPE_BACKUPCODE);
    }

    /**
     * @Given there are :arg1 backup codes
     */
    public function thereAreXBackupCodes($count)
    {
        $backupMfa = $this->getMfa(Mfa::TYPE_BACKUPCODE);

        if (empty($backupMfa)) {
            return;
        }

        MfaBackupcode::createBackupCodes($backupMfa->id, $count);
        $backupMfa->refresh();
    }

    /**
     * @Given the mfa event type is set to :arg1
     */
    public function theMfaEventTypeIsSetTo($eventType) {
        $this->mfaEventType = $eventType;
    }


    /**
     * @Given a backup code mfa option was used :arg1 days ago
     */
    public function aBackupCodeMfaOptionWasXUsedDaysAgo($lastUsedDaysAgo)
    {
        $this->createMfa(Mfa::TYPE_BACKUPCODE, $lastUsedDaysAgo);
    }

    /**
     * @When I check if a mfa option added email should be sent
     */
    public function iCheckIfAMfaOptionAddedEmailShouldBeSent()
    {
        $this->tempUser->refresh();
        $this->mfaOptionAddedEmailShouldBeSent = $this->fakeEmailer->shouldSendMfaOptionAddedMessageTo(
            $this->tempUser,
            $this->mfaEventType
        );
    }

    /**
     * @When I check if a mfa enabled email should be sent
     */
    public function iCheckIfAMfaEnabledEmailShouldBeSent()
    {
        $this->mfaEnabledEmailShouldBeSent = $this->fakeEmailer->shouldSendMfaEnabledMessageTo(
            $this->tempUser,
            $this->mfaEventType
        );
    }

    /**
     * @When I check if a mfa option removed email should be sent
     */
    public function iCheckIfAMfaOptionRemovedEmailShouldBeSent()
    {
        $this->mfaOptionRemovedEmailShouldBeSent = $this->fakeEmailer->shouldSendMfaOptionRemovedMessageTo(
            $this->tempUser,
            $this->mfaEventType
        );
    }

    /**
     * @When I check if a mfa disabled email should be sent
     */
    public function iCheckIfAMfaDisabledEmailShouldBeSent()
    {
        $this->mfaDisabledEmailShouldBeSent = $this->fakeEmailer->shouldSendMfaDisabledMessageTo(
            $this->tempUser,
            $this->mfaEventType
        );
    }

    /**
     * @When I check if a lost security key email should be sent
     */
    public function iCheckIfALostSecurityKeyEmailShouldBeSent()
    {
        $this->lostKeyEmailShouldBeSent = $this->fakeEmailer->shouldSendLostSecurityKeyMessageTo($this->tempUser);
    }


    /**
     * @When I check if a get backup codes email should be sent
     */
    public function iCheckIfAGetBackupCodesEmailShouldBeSent()
    {
        $this->getBackupCodesEmailShouldBeSent = $this->fakeEmailer->shouldSendGetBackupCodesMessageTo($this->tempUser);
    }


    /**
     * @When I check if a refresh backup codes email should be sent
     */
    public function iCheckIfARefreshBackupCodesEmailShouldBeSent()
    {
        $this->refreshBackupCodesEmailShouldBeSent = $this->fakeEmailer->shouldSendRefreshBackupCodesMessageTo($this->tempUser);
    }

    /**
     * @Then I see that a mfa option added email should NOT be sent
     */
    public function iSeeThatAMfaOptionAddedEmailShouldNotBeSent()
    {
        assert::false($this->mfaOptionAddedEmailShouldBeSent);
    }

    /**
     * @Then I see that a mfa option added email should be sent
     */
    public function iSeeThatAMfaOptionAddedEmailShouldBeSent()
    {
        assert::true($this->mfaOptionAddedEmailShouldBeSent);
    }

    /**
     * @Then I see that a mfa enabled email should NOT be sent
     */
    public function iSeeThatAMfaEnabledEmailShouldNotBeSent()
    {
        assert::false($this->mfaEnabledEmailShouldBeSent);
    }

    /**
     * @Then I see that a mfa enabled email should be sent
     */
    public function iSeeThatAMfaEnabledEmailShouldBeSent()
    {
        assert::true($this->mfaEnabledEmailShouldBeSent);
    }

    /**
     * @Then I see that a mfa option removed email should NOT be sent
     */
    public function iSeeThatAMfaOptionRemovedEmailShouldNotBeSent()
    {
        assert::false($this->mfaOptionRemovedEmailShouldBeSent);
    }

    /**
     * @Then I see that a mfa option removed email should be sent
     */
    public function iSeeThatAMfaOptionRemovedEmailShouldBeSent()
    {
        assert::true($this->mfaOptionRemovedEmailShouldBeSent);
    }

    /**
     * @Then I see that a mfa disabled email should NOT be sent
     */
    public function iSeeThatAMfaDisabledEmailShouldNotBeSent()
    {
        assert::false($this->mfaDisabledEmailShouldBeSent);
    }

    /**
     * @Then I see that a mfa disabled email should be sent
     */
    public function iSeeThatAMfaDisabledEmailShouldBeSent()
    {
        assert::true($this->mfaDisabledEmailShouldBeSent);
    }

    /**
     * @Then I see that a lost security key email should NOT be sent
     */
    public function iSeeThatALostSecurityKeyEmailShouldNotBeSent()
    {
        assert::false($this->lostKeyEmailShouldBeSent);
    }

    /**
     * @Then I see that a lost security key email should be sent
     */
    public function iSeeThatALostSecurityKeyEmailShouldBeSent()
    {
        assert::true($this->lostKeyEmailShouldBeSent);
    }

    /**
     * @Then I see that a get backup codes email should NOT be sent
     */
    public function iSeeThatAGetBackupCodesEmailShouldNotBeSent()
    {
        assert::false($this->getBackupCodesEmailShouldBeSent);
    }

    /**
     * @Then I see that a get backup codes email should be sent
     */
    public function iSeeThatAGetBackupCodesEmailShouldBeSent()
    {
        assert::true($this->getBackupCodesEmailShouldBeSent);
    }

    /**
     * @Then I see that a refresh backup codes email should NOT be sent
     */
    public function iSeeThatARefreshBackupCodesEmailShouldNotBeSent()
    {
        assert::false($this->refreshBackupCodesEmailShouldBeSent);
    }

    /**
     * @Then I see that a refresh backup codes email should be sent
     */
    public function iSeeThatARefreshBackupCodesEmailShouldBeSent()
    {
        assert::true($this->refreshBackupCodesEmailShouldBeSent);
    }
}
