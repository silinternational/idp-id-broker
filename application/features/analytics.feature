
Feature: User
  In order to report about mfa usage
  I need to be able to count active users with diffent kinds of verified mfa records

  Background:
    Given the user store is empty

  Scenario: Get a count of active users with a verified mfa
    Given I create a new user
        And that user has a backup code mfa record
      And I create a new user
        And that user has a verified totp mfa record
      And I create a new user
        And that user has a verified webauthn mfa record
        And that user has another verified webauthn mfa record
      And I create a new user
        And that user has an unverified totp mfa record
      And I create a new user
        And that user has an unverified webauthn mfa record
      And I create a new user
    When I get the count of active users with a verified mfa
    Then the count of active users with a verified mfa should be 3

  Scenario: Get a count of active users with a backup code mfa
    Given I create a new user
        And that user has a backup code mfa record
      And I create a new user
        And that user has a verified totp mfa record
      And I create a new user
        And that user has an unverified webauthn mfa record
      And I create a new user
    When I get the count of active users with a backup code mfa
    Then the count of active users with a backup code mfa should be 1

  Scenario: Get a count of active users with a verified totp mfa
    Given I create a new user
        And that user has a backup code mfa record
      And I create a new user
        And that user has a verified totp mfa record
      And I create a new user
        And that user has a verified totp mfa record
      And I create a new user
        And that user has an unverified totp mfa record
      And I create a new user
        And that user has a verified webauthn mfa record
      And I create a new user
    When I get the count of active users with a verified totp mfa
    Then the count of active users with a verified totp mfa should be 2

  Scenario: Get a count of active users with a verified webauthn mfa
    Given I create a new user
        And that user has a backup code mfa record
      And I create a new user
        And that user has a verified totp mfa record
      And I create a new user
        And that user has a verified webauthn mfa record
        And that user has another verified webauthn mfa record
      And I create a new user
        And that user has an unverified webauthn mfa record
      And I create a new user
    When I get the count of active users with a verified webauthn mfa
    Then the count of active users with a verified webauthn mfa should be 1

  Scenario: Get a count of active users with a password
    Given I create a new user with a password
      And I create a new user with a password
      And I create a new user with a password
      And I create a new user without a password
      And I create a new user without a password
    When I get the count of active users with a password
    Then the count of active users with a password should be 3

  Scenario: Get the average number of mfas per active user with a verified mfa
    Given I create a new user
        And that user has a backup code mfa record
      And I create a new user
        And that user has a verified totp mfa record
        And that user has a backup code mfa record
      And I create a new user
        And that user has a verified webauthn mfa record
        And that user has another verified webauthn mfa record
        And that user has a backup code mfa record
      And I create a new user
        And that user has an unverified webauthn mfa record
      And I create a new inactive user
        And that user has a backup code mfa record
      And I create a new user
    When I get the average number of mfas per active user with mfas
    Then the average number of mfas per active user with mfas should be 2

  Scenario: Get a count of active users with require mfa
    Given I create a new user with require mfa
      And I create a new user with require mfa
      And I create a new user
      And I create a new user
      And I create a new user
    When I get the count of active users with require mfa
    Then the count of active users with require mfa should be 2

  Scenario: Get a count of active users with either webauthn or totp but not backupcodes
    Given I create a new user
      And I create another new user
      And that user has a backup code mfa record
      And I create another new user
      And that user has a verified totp mfa record
      And I create another new user
      And that user has a verified webauthn mfa record
      And I create another new user
      And that user has a verified totp mfa record
      And that user has a verified webauthn mfa record
      And I create another new user
      And that user has a backup code mfa record
      And that user has a verified totp mfa record
      And I create another new user
      And that user has an unverified totp mfa record
      And I create a new inactive user
      And that user has a verified webauthn mfa record
    When I get the count of active users with webauthn or totp but not backupcodes
    Then the count of active users with webauthn or totp but not backupcodes should be 2

    Scenario: Get a count of active users with a personal email but no recovery methods
      Given I create a new user
        And I create another new user
        And that user has a personal email address
        And I create another new user
        And that user has a personal email address
        And that user has a recovery method
        And I create another new user
        And that user has a personal email address
        And that user has an unverified recovery method
        And I create a new inactive user
        And that user has a personal email address
      When I get the count of active users with a personal email but no recovery methods
      Then the count of active users with a personal email but no recovery methods should be 2
