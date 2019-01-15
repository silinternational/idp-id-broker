Feature: Invite

  Scenario: Requesting a new, unexpired invite code
    Given the database contains a user with no invite codes
    When I request an invite code
    Then I receive a code that is not expired
      And the code should be in UUID format

  Scenario: Request an invite code when one already exists, before its expiration
    Given the database contains a user with a non-expired invite code
    When I request an invite code
    Then I receive a code that is not expired

  Scenario: Request an invite code when one already exists, after its expiration
    Given the database contains a user with a expired invite code
    When I request an invite code
    Then I receive a new code
      And the new code is not expired
