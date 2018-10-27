<?php
namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use common\models\Method;
use common\models\User;
use Webmozart\Assert\Assert;

class MethodContext extends \FeatureContext
{
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

}
