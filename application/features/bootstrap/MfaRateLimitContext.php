<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use common\helpers\MySqlDateTime;
use common\models\EmailLog;
use common\models\Mfa;
use common\models\MfaFailedAttempt;
use common\models\User;
use Webmozart\Assert\Assert;
use Yii;
use yii\web\TooManyRequestsHttpException;

class MfaRateLimitContext extends YiiContext
{
    /** @var int */
    protected $mfaId = null;

    /** @var bool */
    protected $mfaVerifyResult = null;

    protected $mfaVerifyException = null;

    /** @var string[] */
    protected $validBackupCodes = [];

    /**
     * Create a new user in the database with the given username (and other
     * details based off that username). If a user already exists with that
     * username, they will be deleted.
     *
     * @param string $username
     * @return User
     */
    protected function createNewUserInDatabase($username)
    {
        $existingUser = User::findByUsername($username);
        if ($existingUser !== null) {
            Assert::notSame($existingUser->delete(), false);
        }

        $user = new User([
            'email' => $username . '@example.com',
            'employee_id' => (string)uniqid(),
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => $username,
        ]);
        $user->scenario = User::SCENARIO_NEW_USER;
        Assert::true(
            $user->save(),
            var_export($user->getErrors(), true)
        );
        Assert::notNull($user);
        return $user;
    }

    protected function generateBackupCodeNotIn($validBackupCodes)
    {
        Assert::isArray($validBackupCodes);

        do {
            $backupCode = substr(random_int(100000000, 200000000), 1);
        } while (in_array($backupCode, $validBackupCodes));

        Assert::false(in_array($backupCode, $validBackupCodes));
        return $backupCode;
    }

    protected function submitBackupCode($mfaId, $backupCode)
    {
        $mfa = Mfa::findOne($mfaId);
        Assert::notNull($mfa);
        try {
            $this->mfaVerifyResult = $mfa->verify($backupCode);
        } catch (TooManyRequestsHttpException $e) {
            $this->mfaVerifyException = $e;
        }
    }

    /**
     * @Given I have a user with backup codes available
     */
    public function iHaveAUserWithBackupCodesAvailable()
    {
        $user = $this->createNewUserInDatabase('has_backupcodes');
        $mfaCreateResult = Mfa::create($user->id, Mfa::TYPE_BACKUPCODE);

        $this->mfaId = $mfaCreateResult['id'];
        Assert::notEmpty($this->mfaId);
        $this->validBackupCodes = $mfaCreateResult['data'];
    }

    /**
     * @Given that MFA method has no recent failures
     */
    public function thatMfaMethodHasNoRecentFailures()
    {
        $this->thatMfaMethodShouldHaveRecentFailure(0);
    }

    /**
     * @When I submit a correct backup code
     */
    public function iSubmitACorrectBackupCode()
    {
        Assert::notEmpty($this->validBackupCodes);
        $correctBackupCode = array_pop($this->validBackupCodes);
        $this->submitBackupCode($this->mfaId, $correctBackupCode);
    }

    /**
     * @Then the backup code should be accepted
     */
    public function theBackupCodeShouldBeAccepted()
    {
        Assert::true($this->mfaVerifyResult);
    }

    /**
     * @When I submit an incorrect backup code
     */
    public function iSubmitAnIncorrectBackupCode()
    {
        $incorrectBackupCode = $this->generateBackupCodeNotIn(
            $this->validBackupCodes
        );
        $this->submitBackupCode($this->mfaId, $incorrectBackupCode);
    }

    /**
     * @Then that MFA method should have :number recent failure(s)
     */
    public function thatMfaMethodShouldHaveRecentFailure(int $number)
    {
        $mfa = Mfa::findOne($this->mfaId);
        Assert::notNull($mfa);
        Assert::same(
            (string)$mfa->countRecentFailures(),
            (string)$number
        );
    }

    /**
     * @Given that MFA method has nearly too many recent failures
     */
    public function thatMfaMethodHasNearlyTooManyRecentFailures()
    {
        $mfa = Mfa::findOne($this->mfaId);
        Assert::notNull($mfa);

        $initialCount = $mfa->countRecentFailures();
        Assert::integerish($initialCount);
        Assert::lessThan(
            $initialCount,
            MfaFailedAttempt::RECENT_FAILURE_LIMIT
        );

        $desiredCount = MfaFailedAttempt::RECENT_FAILURE_LIMIT - 1;

        for ($i = $initialCount; $i < $desiredCount; $i++) {
            $mfa->recordFailedAttempt();
        }

        Assert::same($mfa->countRecentFailures(), $desiredCount);
    }

    /**
     * @Given that MFA method has too many recent failures
     */
    public function thatMfaMethodHasTooManyRecentFailures()
    {
        $this->thatMfaMethodHasNearlyTooManyRecentFailures();

        $mfa = Mfa::findOne($this->mfaId);
        Assert::notNull($mfa);
        $mfa->recordFailedAttempt();

        Assert::same(
            $mfa->countRecentFailures(),
            MfaFailedAttempt::RECENT_FAILURE_LIMIT
        );
    }

    /**
     * @Then I should be told to wait and try later
     */
    public function iShouldBeToldToWaitAndTryLater()
    {
        Assert::isInstanceOf(
            $this->mfaVerifyException,
            TooManyRequestsHttpException::class
        );
    }

    /**
     * @Then an MFA rate-limit email should have been sent to that user
     */
    public function anEmailShouldHaveBeenSentToThatUser()
    {
        $mfa = Mfa::findOne($this->mfaId);
        Assert::notNull($mfa);

        $matchingFakeEmails = $this->fakeEmailer->getFakeEmailsOfTypeSentToUser(
            EmailLog::MESSAGE_TYPE_MFA_RATE_LIMIT,
            $mfa->user->email,
            $mfa->user
        );
        Assert::greaterThan(count($matchingFakeEmails), 0, sprintf(
            'Did not find any %s emails sent to that user.',
            EmailLog::MESSAGE_TYPE_MFA_RATE_LIMIT
        ));
    }

    /**
     * @Then that MFA rate-limit activation should have been logged
     */
    public function thatMfaRateLimitActivationShouldHaveBeenLogged()
    {
        Yii::getLogger()->flush();

        $loggedMessagesJson = $this->fakeLogTarget->getLoggedMessagesJson();
        Assert::contains($loggedMessagesJson, 'MFA rate limit triggered');
    }

    /**
     * @Given that MFA method had too many failures, but they are not recent
     */
    public function thatMfaMethodHadTooManyFailuresButTheyAreNotRecent()
    {
        $mfa = Mfa::findOne($this->mfaId);
        Assert::notNull($mfa);

        $notRecent = MySqlDateTime::relativeTime('-6 minutes');
        for ($i = 0; $i <= MfaFailedAttempt::RECENT_FAILURE_LIMIT; $i++) {
            $mfaFailedAttempt = new MfaFailedAttempt([
                'mfa_id' => $mfa->id,
                'at_utc' => $notRecent,
            ]);
            Assert::true($mfaFailedAttempt->save());
            Assert::true($mfaFailedAttempt->refresh());
            Assert::same($mfaFailedAttempt->at_utc, $notRecent, json_encode([
                'at_utc' => $mfaFailedAttempt->at_utc,
                'notRecent' => $notRecent,
            ], JSON_PRETTY_PRINT));
        }
    }
}
