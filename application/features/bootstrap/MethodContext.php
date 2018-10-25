<?php
namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use common\models\Method;
use common\models\User;
use Webmozart\Assert\Assert;

class MethodContext extends \FeatureContext
{

    /**
     * @Given that user has a verified Method
     */
    public function thatUserHasAVerifiedMethod()
    {
        $user = User::findOne(['employee_id' => $this->tempEmployeeId]);
        Assert::notEmpty($user, 'Unable to find that user.');
        $method = new Method([
            'user_id' => $user->id,
            'verified' => 1,
            'value' => 'example001@example.com',
        ]);
        Assert::true($method->save(), 'Failed to add that MFA record to the database.');
    }

}
