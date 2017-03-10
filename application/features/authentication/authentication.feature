Feature: Authentication
  In order to identify a specific user
  As an authorized user
  I need to be able to authenticate a user based on certain credentials

  Scenario: Create an authentication for a known user with a matching password
    Given I am authorized
      And I receive a username
      And I receive a password
    When I verify the password matches the associated password
    Then I will respond positively

  Scenario: Attempt to create an authentication for an unknown user
    When I realize the username does not exist in my system
    Then I will respond negatively

  Scenario: Attempt to create an authentication without a username
  Scenario: Attempt to create an authentication with an invalid username

  Scenario: Attempt to create an authentication for a known user with a mismatched password
    When I realize the given password has does not match the stored version
    Then I will respond negatively

  Scenario: Attempt to create an authentication without a password
  Scenario: Attempt to create an authentication for a known user with an invalid password

  Scenario: Attempt to retrieve an authentication
  Scenario: Attempt to update an authentication
  Scenario: Attempt to delete an authentication
  Scenario: Attempt to create an authentication
