Feature: MFA

  Scenario: Retrieve MFA records for a User without any MFA records
    Given the requester is authorized
      And the user store is empty
      And I add a user with an "employee_id" of "123"
    When I request "/user/123/mfa" be retrieved
    Then the response status code should be 200
      And I should receive 0 records

  Scenario: Retrieve MFA records for a User with 1 MFA record
    Given the requester is authorized
      And the user store is empty
      And I add a user with an "employee_id" of "123"
      And that user has a verified "backupcode" MFA
    When I request "/user/123/mfa" be retrieved
    Then the response status code should be 200
      And I should receive 1 record

  Scenario: Retrieve a specific MFA record for a specific User
    Given the requester is authorized
      And the user store is empty
      And I add a user with an "employee_id" of "123"
      And that user has a verified "backupcode" MFA with an id of 45
      And I provide the following valid data:
        | property    | value |
        | employee_id | 123   |
    When I request "/mfa/45" be retrieved
    Then the response status code should be 200
      And the response should indicate that 10 backup codes remain
