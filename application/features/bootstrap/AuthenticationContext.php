<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class AuthenticationContext implements Context
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @Given I receive a username
     */
    public function iReceiveAUsername()
    {
        throw new PendingException();
    }

    /**
     * @Given I receive a password
     */
    public function iReceiveAPassword()
    {
        throw new PendingException();
    }

    /**
     * @Given I am authorized
     */
    public function iAmAuthorized()
    {
        throw new PendingException();
    }

    /**
     * @When I verify the password matches the associated password
     */
    public function iVerifyThePasswordMatchesTheAssociatedPassword()
    {
        throw new PendingException();
    }

    /**
     * @Then I will respond positively
     */
    public function iWillRespondPositively()
    {
        throw new PendingException();
    }

    /**
     * @When I realize the username does not exist in my system
     */
    public function iRealizeTheUsernameDoesNotExistInMySystem()
    {
        throw new PendingException();
    }

    /**
     * @Then I will respond negatively
     */
    public function iWillRespondNegatively()
    {
        throw new PendingException();
    }

    /**
     * @When I realize the given password has does not match the stored version
     */
    public function iRealizeTheGivenPasswordHasDoesNotMatchTheStoredVersion()
    {
        throw new PendingException();
    }
}
