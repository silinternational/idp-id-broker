Feature: Recovery Method API
  Messages are exchanged with a RESTful API for the
  purpose of creating and verifying password recovery
  methods.

  Background:
    Given the requester is authorized
    And the user store is empty
    And I add a user with an "employee_id" of "123"

  Scenario: Retrieve Method records for a User without any Method records
    When I request "/user/123/method" be retrieved
    Then the response status code should be 200
    And I should receive 0 records

  Scenario: Retrieve Method records for a User with 1 Method record
    Given user with employee id 123 has a verified Method
    When I request "/user/123/method" be retrieved
    Then the response status code should be 200
    And I should receive 1 record

  Scenario: Retrieve Method records for a User with 1 verified and 1 unverified Method
    Given user with employee id 123 has a verified Method
    And user with employee id 123 has an unverified Method
    When I request "/user/123/method" be retrieved
    Then the response status code should be 200
    And I should receive 2 records

  Scenario: Retrieve a specific Method record
    Given user with employee id 123 has a verified Method
    When I send a "GET" to "/method/{uid}" with a valid uid
    Then the response status code should be 200
    And the following data is returned:
      | property      | value                 |
      | value         | verified@example.com  |

  Scenario: Create a new Method record
    When I provide the following valid data:
      | property     | value                 |
      | employee_id  | 123                   |
      | value        | user123@example.org   |
    And I request "/method/create" be created
    Then the response status code should be 200
    And the following data is returned:
      | property      | value                 |
      | value         | user123@example.org   |
    And a method record exists with a value of "user123@example.org"
    And the following method data should be stored:
      | property              | value                 |
      | value                 | user123@example.org   |
      | verified              | 0                     |
      | verification_attempts | 1                     |
#    And verification_code should be stored as ?
#    And verification_expires should be stored as ? UTC
#    And created should be stored as now UTC

### TODO: Try to create a method using the primary (or spouse/supervisor) address

 Scenario: Resend a method verification
   Given user with employee id 123 has an unverified Method
   When I send a "PUT" to "/method/{uid}/resend" with a valid uid
   Then the response status code should be 204
   And a method record exists with a value of "unverified@example.com"
   And the following method data should be stored:
     | property              | value                 |
     | verified              | 0                     |

 Scenario: Verify a Method
   Given user with employee id 123 has an unverified Method
   When I send the correct code to verify that Method
   Then the response status code should be 200
   And the following data is returned:
     | property      | value                    |
     | value         | unverified@example.com   |
   And a method record exists with a value of "unverified@example.com"
   And the following method data should be stored:
     | property              | value                 |
     | verified              | 1                     |
     | verification_code     | null                  |
     | verification_attempts | null                  |
     | verification_expires  | null                  |

  Scenario: Delete a Method
    Given user with employee id 123 has an unverified Method
    When I send a "DELETE" to "/method/{uid}" with a valid uid
    Then the response status code should be 204

