<?php
namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use common\helpers\MySqlDateTime;
use common\models\Mfa;
use common\models\User;
use Sil\SilIdBroker\Behat\Context\YiiContext;
use Webmozart\Assert\Assert;
use yii\helpers\Json;

class AnalyticsContext extends YiiContext
{
    /** @var User */
    protected $tempUser;



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

    protected function createMfa($user, $type, $alreadyVerified=true)
    {
        $mfa = new Mfa();
        $mfa->user_id = $user->id;
        $mfa->type = $type;
        if ($alreadyVerified || $type === Mfa::TYPE_BACKUPCODE) {
            $mfa->verified = 1;
        }
        $mfa->save();
    }

    /**
     * @Given that no mfas or users exist
     */
    public function noMfasOrUsersExist()
    {
        Mfa::deleteAll();
        User::deleteAll();
    }

    /**
     * @Given I create a new user
     */
    public function iCreateANewUser()
    {
        $this->tempUser = $this->createNewUser();
    }

    /**
     * @Given that user has a backup code mfa record
     */
    public function thatUserHasABackupCodeMfaRecord()
    {
        $this->createMfa($this->tempUser, mfa::TYPE_BACKUPCODE);
    }

    /**
     * @Given that user has a verified totp mfa record
     */
    public function thatUserHasAVerifiedTotpMfaRecord()
    {
        $this->createMfa($this->tempUser, mfa::TYPE_TOTP);
    }

    /**
     * @Given that user has an unverified totp mfa record
     */
    public function thatUserHasAnUnverifiedTotpMfaRecord()
    {
        $this->createMfa($this->tempUser, mfa::TYPE_TOTP, false);
    }

    /**
     * @Given that user has a verified u2f mfa record
     */
    public function thatUserHasAVerifiedU2fMfaRecord()
    {
        $this->createMfa($this->tempUser, mfa::TYPE_U2F);
    }

    /**
     * @Given that user has an unverified u2f mfa record
     */
    public function thatUserHasAnUnverifiedU2fMfaRecord()
    {
        $this->createMfa($this->tempUser, mfa::TYPE_U2F, false);
    }

    /**
     * @When I get the count of active users with a verified mfa
     */
    public function iGetTheCountOfActiveUsersWithAVerifiedMfa()
    {
        $query = User::getQueryOfUsersWithMfa();
        $this->mfaCount = $query->count();
    }

    /**
     * @When I get the count of active users with a backup code mfa
     */
    public function iGetTheCountOfActiveUsersWithABackupCodeMfa()
    {
        $query = User::getQueryOfUsersWithMfa(Mfa::TYPE_BACKUPCODE);
        $this->mfaCount = $query->count();
    }

    /**
     * @When I get the count of active users with a verified totp mfa
     */
    public function iGetTheCountOfActiveUsersWithAVerifiedTotpMfa()
    {
        $query = User::getQueryOfUsersWithMfa(Mfa::TYPE_TOTP);
        $this->mfaCount = $query->count();
    }

    /**
     * @When I get the count of active users with a verified u2f mfa
     */
    public function iGetTheCountOfActiveUsersWithAVerifiedU2fMfa()
    {
        $query = User::getQueryOfUsersWithMfa(Mfa::TYPE_U2F);
        $this->mfaCount = $query->count();
    }

    /**
     * @Then the count of active users with a verified mfa should be :arg1
     */
    public function theCountOfActiveUsersWithAVerifiedMfaShouldBe($number)
    {
        Assert::same(
            $this->mfaCount,
            $number
        );
    }

    /**
     * @Then the count of active users with a backup code mfa should be :arg1
     */
    public function theCountOfActiveUsersWithABackupCodeMfaShouldBe($number)
    {
        Assert::same(
            $this->mfaCount,
            $number
        );
    }

    /**
     * @Then the count of active users with a verified totp mfa should be :arg1
     */
    public function theCountOfActiveUsersWithAVerifiedTotpMfaShouldBe($number)
    {
        Assert::same(
            $this->mfaCount,
            $number
        );
    }

    /**
     * @Then the count of active users with a verified u2f mfa should be :arg1
     */
    public function theCountOfActiveUsersWithAVerifiedU2fMfaShouldBe($number)
    {
        Assert::same(
            $this->mfaCount,
            $number
        );
    }
}
