<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;

/**
 * Defines application features from the specific context.
 */
class UserContext implements Context
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
     * @Given /^the user does not already exist$/
     */
    public function theUserDoesNotAlreadyExist()
    {
        throw new PendingException();
    }

    /**
     * @When /^I receive a request to create a user$/
     */
    public function iReceiveARequestToCreateAUser()
    {
        throw new PendingException();
    }

    /**
     * @Given /^I receive a valid employee id$/
     */
    public function iReceiveAValidEmployeeId()
    {
        throw new PendingException();
    }

    /**
     * @Given /^I receive a valid first name$/
     */
    public function iReceiveAValidFirstName()
    {
        throw new PendingException();
    }

    /**
     * @Given /^I receive a valid last name$/
     */
    public function iReceiveAValidLastName()
    {
        throw new PendingException();
    }

    /**
     * @Given /^I receive a valid display name$/
     */
    public function iReceiveAValidDisplayName()
    {
        throw new PendingException();
    }

    /**
     * @Given /^I receive a valid username$/
     */
    public function iReceiveAValidUsername()
    {
        throw new PendingException();
    }

    /**
     * @Given /^I receive a valid gmail$/
     */
    public function iReceiveAValidGmail()
    {
        throw new PendingException();
    }

    /**
     * @Then /^a new id should be created$/
     */
    public function aNewIdShouldBeCreated()
    {
        throw new PendingException();
    }

    /**
     * @Given /^the employee id should be stored$/
     */
    public function theEmployeeIdShouldBeStored()
    {
        throw new PendingException();
    }

    /**
     * @Given /^the first name should be stored$/
     */
    public function theFirstNameShouldBeStored()
    {
        throw new PendingException();
    }

    /**
     * @Given /^the last name should be stored$/
     */
    public function theLastNameShouldBeStored()
    {
        throw new PendingException();
    }

    /**
     * @Given /^the display name should be stored$/
     */
    public function theDisplayNameShouldBeStored()
    {
        throw new PendingException();
    }

    /**
     * @Given /^the username should be stored$/
     */
    public function theUsernameShouldBeStored()
    {
        throw new PendingException();
    }

    /**
     * @Given /^the email should be stored$/
     */
    public function theEmailShouldBeStored()
    {
        throw new PendingException();
    }

    /**
     * @Given /^the password hash should still be empty$/
     */
    public function thePasswordHashShouldStillBeEmpty()
    {
        throw new PendingException();
    }

    /**
     * @Given /^active should be stored as a yes$/
     */
    public function activeShouldBeStoredAsAYes()
    {
        throw new PendingException();
    }

    /**
     * @Given /^locked should be stored as a no$/
     */
    public function lockedShouldBeStoredAsANo()
    {
        throw new PendingException();
    }

    /**
     * @Given /^the requester is authorized$/
     */
    public function theRequesterIsAuthorized()
    {
        throw new PendingException();
    }

    /**
     * @Given /^the last synced date should be stored as the instant it was stored$/
     */
    public function theLastSyncedDateShouldBeStoredAsTheInstantItWasStored()
    {
        throw new PendingException();
    }

    /**
     * @Given /^the last synced date should be stored in UTC$/
     */
    public function theLastSyncedDateShouldBeStoredInUTC()
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
}
