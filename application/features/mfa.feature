Feature: MFA

  Background:
    Given the requester is authorized
      And the user store is empty
      And I add a user with an "employee_id" of "123"

  Scenario: Retrieve MFA records for a User without any MFA records
    When I request "/user/123/mfa" be retrieved
    Then the response status code should be 200
      And I should receive 0 records

  Scenario: Retrieve MFA records for a User with a backupcode MFA record
    Given the user has a verified "backupcode" MFA
    When I request "/user/123/mfa" be retrieved
    Then the response status code should be 200
      And I should receive 1 record

  Scenario: Retrieve MFA records for a User with a MfaWebauthn record
    Given the user has a mfaWebauthn with a key_handle_hash of "KHH"
    When I request "/user/123/mfa" be retrieved
    Then the response status code should be 200
    And I should receive 1 record
    And that record should have a data item with the following elements:
      | property      | value          |
      | label         | Security Key-1 |
      | id            | *              |
      | last_used_utc | null           |
      | created_utc   | *              |

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

  Scenario: Create new MFA record of type recovery
    Given I provide the following valid data:
      | property       | value                |
      | employee_id    | 123                  |
      | type           | recovery             |
      | label          | A Label              |
      | recovery_email | recovery@example.com |
    When I request "/mfa" be created
    Then the response status code should be 200
    And the response should contain a data array with 0 items
    And an MFA record exists for an employee_id of "123"
    And the following MFA data should be stored:
      | property       | value                |
      | type           | recovery             |
      | label          | A Label              |
      | verified       | 1                    |

  Scenario: Create new MFA record of type recovery with no recovery email
    Given I provide the following valid data:
      | property    | value    |
      | employee_id | 123      |
      | type        | recovery |
      | label       | A Label  |
    When I request "/mfa" be created
    Then the response status code should be 400

  Scenario: Create new MFA record of type recovery with an invalid recovery email
    Given I provide the following data:
      | property       | value        |
      | employee_id    | 123          |
      | type           | recovery     |
      | label          | A Label      |
      | recovery_email | invalidEmail |
    When I request "/mfa" be created
    Then the response status code should be 400

  Scenario: Create new MFA record of type manager with no manager email
    Given the user does not have a manager email address
      And I provide the following data:
        | property    | value          |
        | employee_id | 123            |
        | type        | manager        |
        | label       | A Label        |
    When I request "/mfa" be created
    Then the response status code should be 400

  Scenario: Request a MFA record of type webauthn
    When the user requests a new webauthn MFA
    Then the response status code should be 200
    And the response body should contain 'publicKey'
    And the response body should contain 'challenge'

  Scenario: Verify a new MFA webauthn registration
    Given the user has requested a new webauthn MFA
    When I request to verify the webauthn Mfa registration
    Then the response status code should be 200
    And the response body should contain "'type' => 'webauthn'"
    And the response body should contain "'label' => 'Security Key'"

  Scenario: Verify a new MFA webauthn registration with a label
    Given the user has requested a new webauthn MFA
    When I request to verify the webauthn Mfa registration with a label of "Yubikey"
    Then the response status code should be 200
    And the response body should contain "'type' => 'webauthn'"
    And the response body should contain "'label' => 'Yubikey'"

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

  Scenario: Update a MfaWebauthn label
    Given the user has a mfaWebauthn with a key_handle_hash of "KHH"
    And I provide the following valid data:
      | property    | value        |
      | employee_id | 123          |
      | label       | A New Label  |
    When I update the mfaWebauthn
    Then the response status code should be 200
      And the property label should contain "A New Label"
      And the following mfaWebauthn data should be stored:
        | property            | value           |
        | label               | A New Label     |

  Scenario: Update a mfaWebauthn label to be blank
    Given the user has a mfaWebauthn with a key_handle_hash of "KHH"
    And I provide the following valid data:
      | property    | value        |
      | employee_id | 123          |
      | label       |              |
    When I update the mfaWebauthn
    Then the response status code should be 400
      And the response body should contain 'Invalid data updating MfaWebauthn label'
      And the following mfaWebauthn data should be stored:
        | property            | value           |
        | label               | Security Key-1  |

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

  Scenario: Verify an recovery MFA code
    Given the user has a verified "recovery" MFA
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

  Scenario: Try to delete a non-existent WebAuthn MFA record
    Given the user has a mfaWebauthn with a key_handle_hash of "u2f"
    When I request to delete the webauthn entry of the MFA with a webauthn_id of 999
    Then the response status code should be 404

  Scenario: Delete a webauthn credential of a webauthn MFA option
    Given the user has a mfaWebauthn with a key_handle_hash of "u2f"
    # This value is from a serverless-mfa-api-go test user that has a credential id of "C10"
    # which gets hashed and base64 encoded to provide the "kI1ykA4kdZIbWA6XHmA-8iTxmVzfR-MCLRLuiK4-boo" value
      And the user has a mfaWebauthn with a key_handle_hash of "kI1ykA4kdZIbWA6XHmA-8iTxmVzfR-MCLRLuiK4-boo"
    When I request to delete the webauthn entry of the MFA
    Then the response status code should be 204
      And the MFA record is still stored

  Scenario: Delete a webauthn credential of a webauthn MFA option
    Given the user has a mfaWebauthn with a key_handle_hash of "kI1ykA4kdZIbWA6XHmA-8iTxmVzfR-MCLRLuiK4-boo"
    When I request to delete the webauthn entry of the MFA
    Then the response status code should be 204
      And the MFA record is not stored

  Scenario: Delete the legacy u2f credential of a webauthn MFA option
    Given the user has a mfaWebauthn with a key_handle_hash of "u2f"
    When I request to delete the webauthn entry of the MFA
    Then the response status code should be 204
      And the MFA record is not stored

  Scenario: Exception to delete the credential of a backupcode MFA option
    Given the user has a verified "backupcode" MFA
    When I request to delete the webauthn entry of the MFA with a webauthn_id of 999
    Then the response status code should be 403
      And 10 codes should be stored

