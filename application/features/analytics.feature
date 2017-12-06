
Feature: User
  In order to report about mfa usage
  I need to be able to count active users with diffent kinds of verified mfa records

  Scenario: Get a count of active users with a verified mfa
    Given that no mfas or users exist
      And I create a new user
        And that user has a backup code mfa record
      And I create a new user
        And that user has a verified totp mfa record
      And I create a new user
        And that user has a verified u2f mfa record
        And that user has another verified u2f mfa record
      And I create a new user
        And that user has an unverified totp mfa record
      And I create a new user
        And that user has an unverified u2f mfa record
      And I create a new user
    When I get the count of active users with a verified mfa
    Then the count of active users with a verified mfa should be 3

  Scenario: Get a count of active users with a backup code mfa
    Given that no mfas or users exist
      And I create a new user
        And that user has a backup code mfa record
      And I create a new user
        And that user has a verified totp mfa record
      And I create a new user
        And that user has an unverified u2f mfa record
      And I create a new user
    When I get the count of active users with a backup code mfa
    Then the count of active users with a backup code mfa should be 1

  Scenario: Get a count of active users with a verified totp mfa
    Given that no mfas or users exist
      And I create a new user
        And that user has a backup code mfa record
      And I create a new user
        And that user has a verified totp mfa record
      And I create a new user
        And that user has a verified totp mfa record
      And I create a new user
        And that user has an unverified totp mfa record
      And I create a new user
        And that user has a verified u2f mfa record
      And I create a new user
    When I get the count of active users with a verified totp mfa
    Then the count of active users with a verified totp mfa should be 2

  Scenario: Get a count of active users with a verified u2f mfa
    Given that no mfas or users exist
      And I create a new user
        And that user has a backup code mfa record
      And I create a new user
        And that user has a verified totp mfa record
      And I create a new user
        And that user has a verified u2f mfa record
        And that user has another verified u2f mfa record
      And I create a new user
        And that user has an unverified u2f mfa record
      And I create a new user
    When I get the count of active users with a verified u2f mfa
    Then the count of active users with a verified u2f mfa should be 1