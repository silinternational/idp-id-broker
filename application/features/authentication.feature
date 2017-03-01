Feature: Authentication
  In order to identify a specific user
  As an unknown user
  I need to be able to authenticate a user based on a password

  Background:
    Given I receive a username
    And I receive a password

  Scenario: Authenticate a known user with a matching password
    When I verify it matches the existing password
    Then I will respond positively

  Scenario: Attempt to authenticate an unknown user
    When I realize the username does not exist in my system
    Then I will respond negatively

  Scenario: Attempt to authenticate a known user with a mismatched password
    When I realize the given password has does not match the stored version
    Then I will respond negatively
