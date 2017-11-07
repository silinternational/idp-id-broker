<?php
namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use common\models\Mfa;
use common\models\User;
use Sil\SilIdBroker\Behat\Context\fakes\FakeOfflineLdap;
use Webmozart\Assert\Assert;

class MfaRateLimitContext extends YiiContext
{
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
            'employee_id' => (string)time(),
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

    /**
     * @Given I have a user with backup codes available
     */
    public function iHaveAUserWithBackupCodesAvailable()
    {
        $user = $this->createNewUserInDatabase('has_backupcodes_' . time());
        $mfa = Mfa::create($user->id, Mfa::TYPE_BACKUPCODE);
    }

    /**
     * @Given that MFA method has no recent failures
     */
    public function thatMfaMethodHasNoRecentFailures()
    {
        throw new PendingException();
    }

    /**
     * @When I submit a correct backup code
     */
    public function iSubmitACorrectBackupCode()
    {
        throw new PendingException();
    }

    /**
     * @Then the backup code should be accepted
     */
    public function theBackupCodeShouldBeAccepted()
    {
        throw new PendingException();
    }
}
