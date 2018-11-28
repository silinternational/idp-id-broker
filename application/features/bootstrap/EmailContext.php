<?php
namespace Sil\SilIdBroker\Behat\Context;

use common\helpers\MySqlDateTime;
use common\models\EmailLog;
use common\models\Method;
use common\models\Mfa;
use common\models\MfaBackupcode;
use common\models\User;
use Sil\SilIdBroker\Behat\Context\YiiContext;
use Webmozart\Assert\Assert;

class EmailContext extends YiiContext
{
    /** @var User */
    protected $tempUser;
    protected $tempUser2;

    /** @var  array of strings */
    protected $tempBackupCodes;

    /** @var bool */
    protected $getBackupCodesEmailHasBeenSent;

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

    /** @var Mfa */
    protected $testMfaOption;

    /** @var Method */
    protected $testMethod;

    const METHOD_EMAIL_ADDRESS = 'method@example.com';

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

    protected function createMfa(
        $type,
        $lastUsedDaysAgo=null,
        $user=null,
        $verified=1
    )
    {
        if ($user ===null) {
            $user = $this->tempUser;
        }
        $mfa = new Mfa();
        $mfa->user_id = $user->id;
        $mfa->type = $type;
        $mfa->verified = $verified;

        $this->testMfaOption = $mfa;

        if ($lastUsedDaysAgo !== null) {
            $diffConfig = "-" . $lastUsedDaysAgo . " days";
            $mfa->last_used_utc= MySqlDateTime::relative($diffConfig);
        }
        Assert::true($mfa->save(), "Could not create new mfa.");
        $user->refresh();
    }

    protected function createTempMfa($type, $verified) {
        $user = $this->tempUser;
        $mfa = new Mfa();
        $mfa->user_id = $user->id;
        $mfa->type = $type;
        $mfa->verified = $verified;

        return $mfa;
    }

    protected function deleteMfaOfType($type) {
        foreach ($this->tempUser->mfas as $mfaOption) {
            if ($mfaOption->type === $type) {
                Assert::true($mfaOption->delete(), 'Could not delete the ' . $type . ' mfa option for the test user.');
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
     * @Given a(nother) (specific) user already exists
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
     * @Given a second user exists with a totp mfa option
    */
    public function aSecondUserExistsWithATotpMfaOption()
    {
        $this->tempUser2 = $this->createNewUser();
        $this->createMfa(Mfa::TYPE_TOTP, null, $this->tempUser2);

    }

    /**
     * @Given a :messageType email was sent :daysAgo days ago to that user
     */
    public function anEmailWasSentXDaysAgoToThatUser(string $messageType, int $daysAgo)
    {
        if ($daysAgo < 0) {
            return;
        }

        $emailLog = new EmailLog([
            'user_id' => $this->tempUser->id,
            'message_type' => $messageType,
        ]);

        if ($daysAgo > 0) {
            $diffConfig = "-" . $daysAgo . " days";
            $dbDate = MySqlDateTime::relative($diffConfig);

            $emailLog->sent_utc = $dbDate;
        }

        Assert::true($emailLog->save(), 'Could not save a new EmailLog for the test');
    }

    /**
     * @Given a :messageType email has been sent to that user
     */
    public function anEmailHasBeenSentToThatUser(string $messageType)
    {

        $emailLog = new EmailLog([
            'user_id' => $this->tempUser->id,
            'message_type' => $messageType,
        ]);

        Assert::true($emailLog->save(), 'Could not save a new EmailLog for the test');
    }

    /**
     * @Given a :messageType email has NOT been sent to that user
     */
    public function anEmailHasNotBeenSentToThatUser(string $messageType)
    {
        EmailLog::deleteAll(['user_id' => $this->tempUser->id, 'message_type' => $messageType]);
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
     * @Given a verified u2f mfa option does exist
     */
    public function aVerifiedU2fMfaOptionDoesExist()
    {
        $this->createMfa(Mfa::TYPE_U2F);
    }


    /**
     * @Given a verified u2f mfa option was just deleted
     */
    public function aVerifiedU2fMfaOptionWasJustDeleted()
    {
        $this->testMfaOption = $this->createTempMfa(Mfa::TYPE_U2F, 1);
        $this->mfaEventType = 'delete_mfa';
    }

    /**
     * @Given an unverified u2f mfa option was just deleted
     */
    public function anUnverifiedU2fMfaOptionWasJustDeleted()
    {
        $this->testMfaOption = $this->createTempMfa(Mfa::TYPE_U2F, 0);
        $this->mfaEventType = 'delete_mfa';
    }

    /**
     * @Given an unverified u2f mfa option does exist
     */
    public function anUnverifiedU2fMfaOptionDoesExist()
    {
        $this->createMfa(Mfa::TYPE_U2F, null, null, 0);
    }

    /**
     * @Given a (verified) u2f mfa option does NOT Exist
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
        $results = Mfa::create($this->tempUser->id, Mfa::TYPE_BACKUPCODE);
        $this->tempBackupCodes = $results['data'];

        $this->tempUser->refresh();
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
    public function thereAreXBackupCodes($desiredCount)
    {
        $backupMfa = $this->getMfa(Mfa::TYPE_BACKUPCODE);

        if (empty($backupMfa)) {
            if ($desiredCount == 0) {
                return;
            }
            throw new InvalidArgumentException('There is no MFA Backup Code option available for the test.');
        }

        $codeCount = count($this->tempBackupCodes);

        Assert::greaterThanEq($codeCount, $desiredCount, 'There are not enough backup codes to run the test.');

        $countDiff = $codeCount - $desiredCount;

        for ($i = 0; $i < $countDiff; $i++) {
            $nextCode = array_shift($this->tempBackupCodes);
            MfaBackupcode::validateAndRemove($backupMfa->id, $nextCode);
        }

        $remainingCodes = MfaBackupcode::findAll(['mfa_id' => $backupMfa->id]);
        Assert::eq(count($remainingCodes), $desiredCount, 'Could not ensure the desired count of backup codes.');
    }

    /**
     * @Given the latest mfa event type was :arg1
     */
    public function theLatestMfaEventTypeWas($eventType) {
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
     * @When a backup code is used up by that user
     */
    public function aBackupCodeIsUsedUpByThatUser()
    {
        $backupMfa = $this->getMfa(Mfa::TYPE_BACKUPCODE);
        $backUpCode = array_shift($this->tempBackupCodes);

        Assert::true(MfaBackupcode::validateAndRemove($backupMfa->id, $backUpCode),
            'Could not remove a backup code.');
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
            $this->mfaEventType,
            $this->testMfaOption
        );
    }

    /**
     * @When I check if a mfa disabled email should be sent
     */
    public function iCheckIfAMfaDisabledEmailShouldBeSent()
    {
        $this->mfaDisabledEmailShouldBeSent = $this->fakeEmailer->shouldSendMfaDisabledMessageTo(
            $this->tempUser,
            $this->mfaEventType,
            $this->testMfaOption
        );
    }

    /**
     * @When I check if a get backup codes email has been sent recently
     */
    public function iCheckIfAGetBackupCodesEmailHasBeenSentRecently()
    {
        $messageType = EmailLog::MESSAGE_TYPE_GET_BACKUP_CODES;
        $this->getBackupCodesEmailHasBeenSent = $this->fakeEmailer->hasReceivedMessageRecently($this->tempUser, $messageType);
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
        $this->refreshBackupCodesEmailShouldBeSent = $this->fakeEmailer->shouldSendRefreshBackupCodesMessage(count($this->tempBackupCodes));
    }

    /**
     * @When I send delayed mfa related emails
     */
    public function iSendDelayedMfaRelatedEmails()
    {
        $this->fakeEmailer->sendDelayedMfaRelatedEmails();
    }

    /**
     * @Then I see that a mfa option added email should NOT be sent
     */
    public function iSeeThatAMfaOptionAddedEmailShouldNotBeSent()
    {
        Assert::false($this->mfaOptionAddedEmailShouldBeSent);
    }

    /**
     * @Then I see that a mfa option added email should be sent
     */
    public function iSeeThatAMfaOptionAddedEmailShouldBeSent()
    {
        Assert::true($this->mfaOptionAddedEmailShouldBeSent);
    }

    /**
     * @Then I see that a mfa enabled email should NOT be sent
     */
    public function iSeeThatAMfaEnabledEmailShouldNotBeSent()
    {
        Assert::false($this->mfaEnabledEmailShouldBeSent);
    }

    /**
     * @Then I see that a mfa enabled email should be sent
     */
    public function iSeeThatAMfaEnabledEmailShouldBeSent()
    {
        Assert::true($this->mfaEnabledEmailShouldBeSent);
    }

    /**
     * @Then I see that a mfa option removed email should NOT be sent
     */
    public function iSeeThatAMfaOptionRemovedEmailShouldNotBeSent()
    {
        Assert::false($this->mfaOptionRemovedEmailShouldBeSent);
    }

    /**
     * @Then I see that a mfa option removed email should be sent
     */
    public function iSeeThatAMfaOptionRemovedEmailShouldBeSent()
    {
        Assert::true($this->mfaOptionRemovedEmailShouldBeSent);
    }

    /**
     * @Then I see that a mfa disabled email should NOT be sent
     */
    public function iSeeThatAMfaDisabledEmailShouldNotBeSent()
    {
        Assert::false($this->mfaDisabledEmailShouldBeSent);
    }

    /**
     * @Then I see that a mfa disabled email should be sent
     */
    public function iSeeThatAMfaDisabledEmailShouldBeSent()
    {
        Assert::true($this->mfaDisabledEmailShouldBeSent);
    }

    /**
     * @Then I see that a get backup codes email has NOT been sent recently
     */
    public function iSeeThatAGetBackupCodesEmailHasNotBeSent()
    {
        Assert::false($this->getBackupCodesEmailHasBeenSent);
    }

    /**
     * @Then I see that a get backup codes email has been sent recently
     */
    public function iSeeThatAGetBackupCodesEmailHasBeenSent()
    {
        Assert::true($this->getBackupCodesEmailHasBeenSent);
    }

    /**
     * @Then I see that a lost security key email should NOT be sent
     */
    public function iSeeThatALostSecurityKeyEmailShouldNotBeSent()
    {
        Assert::false($this->lostKeyEmailShouldBeSent);
    }

    /**
     * @Then I see that a lost security key email should be sent
     */
    public function iSeeThatALostSecurityKeyEmailShouldBeSent()
    {
        Assert::true($this->lostKeyEmailShouldBeSent);
    }

    /**
     * @Then I see that a get backup codes email should NOT be sent
     */
    public function iSeeThatAGetBackupCodesEmailShouldNotBeSent()
    {
        Assert::false($this->getBackupCodesEmailShouldBeSent);
    }

    /**
     * @Then I see that a get backup codes email should be sent
     */
    public function iSeeThatAGetBackupCodesEmailShouldBeSent()
    {
        Assert::true($this->getBackupCodesEmailShouldBeSent);
    }

    /**
     * @Then I see that a refresh backup codes email should NOT be sent
     */
    public function iSeeThatARefreshBackupCodesEmailShouldNotBeSent()
    {
        Assert::false($this->refreshBackupCodesEmailShouldBeSent);
    }

    /**
     * @Then I see that a refresh backup codes email should be sent
     */
    public function iSeeThatARefreshBackupCodesEmailShouldBeSent()
    {
        Assert::true($this->refreshBackupCodesEmailShouldBeSent);
    }

    /**
     * @Then I see that the first user has received a lost-security-key email
     */
    public function iSeeThatTheFirstUserHasReceivedALostSecurityKeyEmail()
    {
        Assert::true($this->fakeEmailer->hasReceivedMessageRecently($this->tempUser->id, EmailLog::MESSAGE_TYPE_LOST_SECURITY_KEY));
    }

    /**
     * @Then I see that the second user has received a get-backup-codes email
     */
    public function iSeeThatTheSecondUserHasReceivedAGetBackupCodesEmail()
    {
        Assert::true($this->fakeEmailer->hasReceivedMessageRecently($this->tempUser2->id, EmailLog::MESSAGE_TYPE_GET_BACKUP_CODES));
    }

    /**
     * @Given no methods exist
     */
    public function noMethodsExist()
    {
        Method::deleteAll();
    }

    /**
     * @param string $value
     */
    protected function createMethod($value)
    {
        $user = $this->tempUser;
        $method = Method::findOrCreate($user->id, $value);

        $this->testMethod = $method;

        Assert::true($method->save(), "Could not create new method.");
    }

    /**
     * @When I create a new recovery method
     */
    public function iCreateANewRecoveryMethod()
    {
        $this->createMethod(self::METHOD_EMAIL_ADDRESS);
    }

    /**
     * @Then a Method Verify email is sent to that method
     */
    public function aMethodVerifyEmailIsSentToThatMethod()
    {
        $matchingFakeEmails = $this->fakeEmailer->getFakeEmailsOfTypeSentToUser(
            EmailLog::MESSAGE_TYPE_METHOD_VERIFY,
            $this->tempUser,
            self::METHOD_EMAIL_ADDRESS
        );

        Assert::greaterThan(count($matchingFakeEmails), 0, sprintf(
            'Did not find any %s emails sent to that address.',
            EmailLog::MESSAGE_TYPE_METHOD_VERIFY
        ));
    }

    /**
     * @Given an unverified method exists
     */
    public function anUnverifiedMethodExists()
    {
        $this->createMethod(self::METHOD_EMAIL_ADDRESS);
    }

    /**
     * @When I request that the verify email is resent
     */
    public function iRequestThatTheVerifyEmailIsResent()
    {
        $this->testMethod->sendVerification();
    }
}
