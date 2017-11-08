Feature: Rate-limiting MFA attempts to protect against brute force attacks

  Scenario: Recording failed MFA attempts
    Given I have a user with backup codes available
      And that MFA method has no recent failures
    When I submit an incorrect backup code
    Then that MFA method should have 1 recent failure

  Scenario: Correct MFA value without any failed attempts
    Given I have a user with backup codes available
      And that MFA method has no recent failures
    When I submit a correct backup code
    Then the backup code should be accepted

  Scenario: Allow some failed MFA attempts (and resetting after success)
    Given I have a user with backup codes available
      And that MFA method has nearly too many recent failures
    When I submit a correct backup code
    Then the backup code should be accepted
      And that MFA method should have 0 recent failures

  Scenario: Prevent too many failed MFA attempts
    Given I have a user with backup codes available
      And that MFA method has too many recent failures
    When I submit a correct backup code
    Then I should be told to wait and try later
      And an MFA rate-limit email should have been sent to that user
      And that MFA rate-limit activation should have been logged

  Scenario: Allow MFA after rate limit has expired
    Given I have a user with backup codes available
      And that MFA method had too many failures, but they are not recent
      And that MFA method has no recent failures
    When I submit a correct backup code
    Then the backup code should be accepted
      And that MFA method should have 0 recent failures
