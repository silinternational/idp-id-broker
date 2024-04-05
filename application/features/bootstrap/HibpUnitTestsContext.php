<?php

namespace Sil\SilIdBroker\Behat\Context;

use common\components\HIBP;
use Webmozart\Assert\Assert;

class HibpUnitTestsContext extends UnitTestsContext
{
    public $password;
    public $hashes;
    public $isPwned;

    /**
     * @Given I have a :password
     */
    public function iHaveAPass($password)
    {
        $this->password = $password;
    }

    /**
     * @When I ask for it as hashes
     */
    public function iAskForItAsHashes()
    {
        $this->hashes = HIBP::asHashes($this->password);
    }

    /**
     * @Then I'll get a :hashPrefix and :hashSuffix
     */
    public function illGetAHashPrefixAndHashSuffix($hashPrefix, $hashSuffix)
    {
        Assert::eq($this->hashes['Prefix'], $hashPrefix);
        Assert::eq($this->hashes['Suffix'], $hashSuffix);
    }

    /**
     * @Given I have a pwned password
     */
    public function iHaveAPwnedPassword()
    {
        $this->password = 'pass123';
    }

    /**
     * @When I ask if it is pwned
     */
    public function iAskIfItIsPwned()
    {
        $this->isPwned = HIBP::isPwned($this->password);
    }

    /**
     * @Then I'll get a true response
     */
    public function illGetATrueResponse()
    {
        Assert::true($this->isPwned);
    }

    /**
     * @Given I have a random password that has not been pwned
     */
    public function iHaveARandomPasswordThatHasNotBeenPwned()
    {
        $this->password = random_bytes(128);
    }

    /**
     * @Then I'll get a false response
     */
    public function illGetAFalseResponse()
    {
        Assert::false($this->isPwned);
    }
}
