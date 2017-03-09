Feature: Authentication
  In order to identify a specific user
  As an unknown user
  I need to be able to authenticate a user based on a password

  Background:
    Given I receive a username
    And I receive a password

  Scenario: Create an authentication for a known user with a matching password
    When I verify it matches the existing password
    Then I will respond positively

  Scenario: Attempt to create an authentication for an unknown user
    When I realize the username does not exist in my system
    Then I will respond negatively

  Scenario: Attempt to create an authentication without a user
  Scenario: Attempt to create an authentication with an invalid user

  Scenario: Attempt to create an authentication for a known user with a mismatched password
    When I realize the given password has does not match the stored version
    Then I will respond negatively

  Scenario: Attempt to create an authentication without a password
  Scenario: Attempt to create an authentication for a known user with an invalid password

  Scenario: Attempt to retrieve an authentication
  Scenario: Attempt to update an authentication
  Scenario: Attempt to delete an authentication
