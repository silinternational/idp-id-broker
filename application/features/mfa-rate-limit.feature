Feature: Rate-limiting MFA attempts to protect against brute force attacks

  Scenario: Correct MFA value without any failed attempts
    Given I have a user with backup codes available
      And that MFA method has no recent failures
    When I submit a correct backup code
    Then the backup code should be accepted

  Scenario: Allow some failed MFA attempts
    Given I have a user with backup codes available
      And that MFA method has nearly too many recent failures
    When I submit a correct backup code
    Then the backup code should be accepted

  Scenario: Prevent too many failed MFA attempts
    Given I have a user with backup codes available
      And that MFA method has too many recent failures
    When I submit a correct backup code
    Then I should be told to wait and try later
