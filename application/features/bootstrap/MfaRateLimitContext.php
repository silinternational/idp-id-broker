<?php
namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use common\models\Mfa;
use common\models\User;
use Sil\SilIdBroker\Behat\Context\fakes\FakeOfflineLdap;
use Webmozart\Assert\Assert;

class MfaRateLimitContext extends YiiContext
{
}
