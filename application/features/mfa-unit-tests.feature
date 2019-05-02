@database
Feature: Unit Tests for the Mfa model

  Scenario Outline: Validate backup codes that may or may not have leading zeros
    Given I have a user with a backup codes mfa option
      And a backup code <withOrWithout> a leading zero was saved and <wasOrNot> shortened
    When a <matchingOrNot> backup code is provided for validation
    Then a backup code match <shouldOrNot> be detected
      And <oneOrNo> backup codes should exist

    Examples:
      | withOrWithout | wasOrNot       | matchingOrNot | shouldOrNot | oneOrNo |
      | with          | was            | matching      | should      | 0       |
      | with          | was            | NOT matching  | should NOT  | 1       |
      | with          | was NOT        | matching      | should      | 0       |
      | with          | was NOT        | NOT matching  | should NOT  | 1       |
      | without       | was NOT        | matching      | should      | 0       |
      | without       | was NOT        | NOT matching  | should NOT  | 1       |

  Scenario: Check that a new backup codes mfa option is seen as "newly verified"
    Given I have a user with a backup codes mfa option
    When I check if the new mfa option is newly verified
    Then I see that the mfa option is newly verified

  Scenario: Check that a new manager rescue mfa option is seen as "newly verified"
    Given I have a user with a manager rescue mfa option
    When I check if the new mfa option is newly verified
    Then I see that the mfa option is newly verified

  Scenario: Check that a new totp mfa option is NOT seen as "newly verified"
    Given I have a user with an unverified totp mfa option
      And the totp mfa option is new
    When I check if the mfa option is newly verified
    Then I see that the mfa option is NOT newly verified

  Scenario: Check that an old totp mfa option that is not verified is NOT seen as "newly verified"
    Given I have a user with an unverified totp mfa option
      And the totp mfa option is old
    When I check if the mfa option is newly verified
    Then I see that the mfa option is NOT newly verified

  Scenario: Check that an old totp mfa option that is verified is seen as "newly verified"
    Given I have a user with a verified totp mfa option
      And the totp mfa option is old
      And the totp mfa option has just been verified
    When I check if the mfa option is newly verified
    Then I see that the mfa option is newly verified

  Scenario: Remove all manager codes for a user when an mfa is verified
    Given I have a user with a backup codes mfa option
      And that user also has a manager rescue mfa option
    When I verify a backup code
    Then I see that the user no longer has a manager rescue mfa option
