Feature: User Unit Tests

  Unit tests for the User model

  Background:
    Given the user store is empty

  Scenario: Create a user with require_mfa = no when mfaRequiredForNewUsers is false
    Given the mfaRequiredForNewUsers config parameter is false
    When I create a new user with a require_mfa property of no
    Then I see the user's require_mfa property is no

  Scenario: Create a user with require_mfa = no when mfaRequiredForNewUsers is true
    Given the mfaRequiredForNewUsers config parameter is true
    When I create a new user with a require_mfa property of no
    Then I see the user's require_mfa property is yes

  Scenario: Ensure require_mfa remains "no" for existing users
    Given the database contains a user
      And that user has a require_mfa property value of no
      And the mfaRequiredForNewUsers config parameter is true
    When I change the user's hide property to yes
    Then I see the user's require_mfa property is no

  Scenario: Set require_mfa to 'no' when mfaAllowDisable is true
    Given the database contains a user
      And that user has a require_mfa property value of yes
      And the mfaAllowDisable config parameter is true
    When I change the user's require_mfa property to no
    Then I see the user's require_mfa property is no

  Scenario: Attempt to set require_mfa to 'no' when mfaAllowDisable is false
    Given the database contains a user
      And that user has a require_mfa property value of yes
      And the mfaAllowDisable config parameter is false
    When I change the user's require_mfa property to no
    Then I see the user's require_mfa property is yes

  Scenario: Ensure require_mfa is remains "no" when mfaAllowDisable is true
    Given the database contains a user with no MFA options
      And that user has a require_mfa property value of no
      And the mfaAllowDisable config parameter is true
    When I add backup codes for that user
    Then I see the user's require_mfa property is no

  Scenario: Ensure require_mfa is set to "yes" when a first option is added
    Given the database contains a user with no MFA options
      And that user has a require_mfa property value of no
      And the mfaAllowDisable config parameter is false
    When I add backup codes for that user
    Then I see the user's require_mfa property is yes

