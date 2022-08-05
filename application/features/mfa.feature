Feature: MFA

  Background:
    Given the requester is authorized
      And the user store is empty
      And I add a user with an "employee_id" of "123"

  Scenario: Retrieve MFA records for a User without any MFA records
    When I request "/user/123/mfa" be retrieved
    Then the response status code should be 200
      And I should receive 0 records

  Scenario: Retrieve MFA records for a User with 1 MFA record
    Given the user has a verified "backupcode" MFA
    When I request "/user/123/mfa" be retrieved
    Then the response status code should be 200
      And I should receive 1 record

  Scenario: Create new MFA record of type backupcode
    Given I provide the following valid data:
      | property    | value          |
      | employee_id | 123            |
      | type        | backupcode     |
      | label       | My Backupcodes |
    When I request "/mfa" be created
    Then the response status code should be 200
      And the response should contain a data array with 10 items
      And an MFA record exists for an employee_id of "123"
      And the following MFA data should be stored:
        | property  | value          |
        | type      | backupcode     |
        | label     | My Backupcodes |
        | verified  | 1              |
      And 10 codes should be stored

  Scenario: Create new MFA record of type manager
    Given the user has a manager email address
      And I provide the following valid data:
        | property    | value          |
        | employee_id | 123            |
        | type        | manager        |
        | label       | A Label        |
    When I request "/mfa" be created
    Then the response status code should be 200
      And the response should contain a data array with 0 items
      And an MFA record exists for an employee_id of "123"
      And the following MFA data should be stored:
        | property  | value          |
        | type      | manager        |
        | label     | A Label        |
        | verified  | 1              |

  Scenario: Create new MFA record of type manager with no manager email
    Given the user does not have a manager email address
      And I provide the following valid data:
        | property    | value          |
        | employee_id | 123            |
        | type        | manager        |
        | label       | A Label        |
    When I request "/mfa" be created
    Then the response status code should be 400

  Scenario: Create new MFA record of type webauthn

  Scenario: Create new MFA record of type totp
#TODO - create a test double for the totp client

  Scenario: Update an MFA label
    Given the user has a verified "backupcode" MFA
      And I provide the following valid data:
        | property    | value        |
        | employee_id | 123          |
        | label       | A New Label  |
    When I update the MFA
    Then the response status code should be 200
      And the property label should contain "A New Label"
      And an MFA record exists for an employee_id of "123"
      And the following MFA data should be stored:
        | property  | value          |
        | label     | A New Label    |

  Scenario: Update an MFA label with an empty string
    Given the user has a verified "backupcode" MFA
    And I provide the following valid data:
      | property    | value        |
      | employee_id | 123          |
      | label       |              |
    When I update the MFA
    Then the response status code should be 200
    And the property label should contain "Printable Codes"
    And an MFA record exists for an employee_id of "123"
    And the following MFA data should be stored:
      | property  | value           |
      | label     | Printable Codes |

  Scenario: Verify a backupcode MFA code
    Given the user has a verified "backupcode" MFA
    When I request to verify one of the codes
    Then the response status code should be 200
      And 9 codes should be stored

  Scenario: Verify a manager MFA code
    Given the user has a verified "manager" MFA
    When I request to verify the code
    Then the response status code should be 200
      And 0 codes should be stored

  Scenario: Delete a backupcode MFA option
    Given the user has a verified "backupcode" MFA
    When I request to delete the MFA
    Then the response status code should be 204
      And 0 codes should be stored
      And the MFA record is not stored

  Scenario: Delete a manager MFA option
    Given the user has a verified "manager" MFA
    When I request to delete the MFA
    Then the response status code should be 204
      And 0 codes should be stored
      And the MFA record is not stored

  Scenario: Exception to delete the missing credential of a webauthn MFA option
    Given the user has a verified webauthn MFA with a key_handle_hash of "u2f"
    When I request to delete the webauthn entry of the MFA with a webauthn_id of 999
    Then the response status code should be 404

  Scenario: Delete the legacy u2f credential of a webauthn MFA option
    Given the user has a verified webauthn MFA with a key_handle_hash of "u2f"
    # This value is from a serverless-mfa-api-go test user that has a credential id of "C10"
    # which gets hashed and base64 encoded to provide the "kI1ykA4kdZIbWA6XHmA-8iTxmVzfR-MCLRLuiK4-boo" value
      And the user has a verified webauthn MFA with a key_handle_hash of "kI1ykA4kdZIbWA6XHmA-8iTxmVzfR-MCLRLuiK4-boo"
    When I request to delete the webauthn entry of the MFA
    Then the response status code should be 204
      And the MFA record is still stored

  Scenario: Delete the legacy u2f credential of a webauthn MFA option
    Given the user has a verified webauthn MFA with a key_handle_hash of "kI1ykA4kdZIbWA6XHmA-8iTxmVzfR-MCLRLuiK4-boo"
    When I request to delete the webauthn entry of the MFA
    Then the response status code should be 204
      And the MFA record is not stored

  Scenario: Exception to delete the credential of a backupcode MFA option
    Given the user has a verified "backupcode" MFA
    When I request to delete the webauthn entry of the MFA with a webauthn_id of 999
    Then the response status code should be 403
      And 10 codes should be stored

