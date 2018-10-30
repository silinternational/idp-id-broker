<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use common\models\Method;
use common\models\User;
use Webmozart\Assert\Assert;

class MethodContext extends \FeatureContext
{
    protected $methodFromDb;
    protected $tempMethodVerificationCode;

    /**
     * @Given /^user with employee id (.*) has (?:a|an) (verified|unverified) Method$/
     */
    public function userHasAMethod($employeeId, $verified)
    {
        $user = User::findOne(['employee_id' => $employeeId]);
        Assert::notEmpty($user, 'Unable to find that user.');
        $method = new Method([
            'user_id' => $user->id,
            'verified' => $verified == 'verified' ? 1 : 0,
            'value' => $verified . '@example.com',
        ]);
        Assert::true($method->save(), 'Failed to add that Method record to the database.');

        $this->tempUid = $method->uid;
        $this->tempMethodVerificationCode = $method->verification_code;
    }

    /**
     * @Then /^a method record exists with (?:a|an) (.*) of "?([^"]*)"?$/
     */
    public function aMethodRecordExistsForThisKey($lookupKey, $lookupValue)
    {
        $this->methodFromDb = Method::findOne([$lookupKey => $lookupValue]);

        Assert::notNull($this->methodFromDb, sprintf(
            'Failed to find a method with a %s of %s.',
            $lookupKey,
            var_export($lookupValue, true)
        ));
    }

    /**
     * @Then the following method data should be stored:
     */
    public function theFollowingMethtodDataIsStored(TableNode $data)
    {
        foreach ($data as $row) {
            $property = $row['property'];
            $expectedValue = $row['value'];

            Assert::eq($this->methodFromDb->$property, $expectedValue);
        }
    }

}
