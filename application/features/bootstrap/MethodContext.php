<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use common\helpers\MySqlDateTime;
use common\models\Method;
use common\models\User;
use Webmozart\Assert\Assert;

class MethodContext extends \FeatureContext
{
    /**
     * @var Method
     */
    protected $tempMethod;

    /**
     * @Given /^user with employee id (.*) has (?:a|an) (verified|unverified) Method "(.*)"$/
     */
    public function userHasAMethod($employeeId, $verified, $value)
    {
        $user = User::findOne(['employee_id' => $employeeId]);
        Assert::notEmpty($user, 'Unable to find that user.');
        $method = new Method([
            'user_id' => $user->id,
            'verified' => $verified == 'verified' ? 1 : 0,
            'value' => $value,
        ]);
        Assert::true(
            $method->save(),
            'Failed to add that Method record to the database. '
            . implode(' ', $method->getFirstErrors())
        );

        $this->tempMethod = $method;
        $this->tempUid = $method->uid;
    }

    /**
     * @Then the following method data should be stored:
     */
    public function theFollowingMethodDataIsStored(TableNode $data)
    {
        foreach ($data as $row) {
            $property = $row['property'];
            $expectedValue = $row['value'];

            Assert::eq($this->methodFromDb->$property, $this->transformNULLs($expectedValue));
        }
    }

    /**
     * @When I send the correct code to verify that Method
     */
    public function iSendTheCorrectCodeToVerifyThatMethod()
    {
        $method = Method::findOne(['uid' => $this->tempUid]);
        $this->iChangeThe('code', $method->verification_code);
        $this->iSendAToWithAValidUid('PUT', '/method/{uid}/verify');
    }

    /**
     * @When I send an incorrect code to verify that Method
     */
    public function iSendAnIncorrectCodeToVerifyThatMethod()
    {
        $this->iChangeThe('code', 'abcdef');
        $this->iSendAToWithAValidUid('PUT', '/method/{uid}/verify');
    }

    /**
     * @Given the verification expiration time has passed
     */
    public function theVerificationExpirationTimeHasPassed()
    {
        $this->tempMethod->verification_expires = MySqlDateTime::relativeTime('-1 hour');
        $this->tempMethod->save();
    }

    /**
     * @Then the method should remain unverified
     */
    public function theMethodShouldRemainUnverified()
    {
        Assert::false($this->methodFromDb->isVerified(), 'Method is verified but should not be.');
    }

    /**
     * @Then the verification should not be expired
     */
    public function theVerificationShouldNotBeExpired()
    {
        Assert::false($this->methodFromDb->isVerificationExpired(), 'Verification is expired but should not be.');
    }

    /**
     * @Then the verification should be expired
     */
    public function theVerificationShouldBeExpired()
    {
        Assert::true($this->methodFromDb->isVerificationExpired(), 'Verification is not expired but should be.');
    }

    /**
     * @Then the verification attempts counter should be :arg1
     */
    public function theVerificationAttemptsCounterShouldBe($arg1)
    {
        Assert::eq($this->methodFromDb->verification_attempts, $arg1);
    }

    /**
     * @Then the verification code should have changed
     */
    public function theVerificationCodeShouldHaveChanged()
    {
        Assert::notEq($this->methodFromDb->verification_code, $this->tempMethod->verification_code);
    }

    /**
     * @Then the verification code should not have changed
     */
    public function theVerificationCodeShouldNotHaveChanged()
    {
        Assert::eq($this->methodFromDb->verification_code, $this->tempMethod->verification_code);
    }
}
