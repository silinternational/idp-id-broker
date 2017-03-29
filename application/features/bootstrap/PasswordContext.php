<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class PasswordContext implements Context
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
     * @Given the requestor is authorized
     */
    public function theRequestorIsAuthorized()
    {
        throw new PendingException();
    }

    /**
     * @Then the last changed date should be stored as the instant it was stored
     */
    public function theLastChangedDateShouldBeStoredAsTheInstantItWasStored()
    {
        throw new PendingException();
    }

    /**
     * @Then the last changed date should be stored in UTC
     */
    public function theLastChangedDateShouldBeStoredInUtc()
    {
        throw new PendingException();
    }

    /**
     * @Then the last synched date should be stored as the instant it was stored
     */
    public function theLastSynchedDateShouldBeStoredAsTheInstantItWasStored()
    {
        throw new PendingException();
    }

    /**
     * @Then the last synched date should be stored in UTC
     */
    public function theLastSynchedDateShouldBeStoredInUtc()
    {
        throw new PendingException();
    }

    /**
     * @Given I receive an existing employee id
     */
    public function iReceiveAnExistingEmployeeId()
    {
        throw new PendingException();
    }

    /**
     * @Given the user does not already have a password
     */
    public function theUserDoesNotAlreadyHaveAPassword()
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
     * @When I receive a request to create a password for a specific user
     */
    public function iReceiveARequestToCreateAPasswordForASpecificUser()
    {
        throw new PendingException();
    }

    /**
     * @Then a new password hash should be stored
     */
    public function aNewPasswordHashShouldBeStored()
    {
        throw new PendingException();
    }

    /**
     * @Given the user already has a password
     */
    public function theUserAlreadyHasAPassword()
    {
        throw new PendingException();
    }

    /**
     * @When I receive a request to change the password for a specific user
     */
    public function iReceiveARequestToChangeThePasswordForASpecificUser()
    {
        throw new PendingException();
    }

    /**
     * @Then the previous password hash should be saved in history
     */
    public function thePreviousPasswordHashShouldBeSavedInHistory()
    {
        throw new PendingException();
    }
}
