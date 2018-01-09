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