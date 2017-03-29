Feature: Authentication
  In order to identify a specific user
  As an authorized user
  I need to be able to authenticate a user based on certain credentials

  Background:
    Given the requester is authorized
      And the user store is empty
      And I provide the following valid data:
        | property     | value                 |
        | employee_id  | 123                   |
        | first_name   | Shep                  |
        | last_name    | Clark                 |
        | display_name | Shep Clark            |
        | username     | shep_clark            |
        | email        | shep_clark@example.org|
      And I request the user be created
      And a record exists with an employee_id of "123"
      And the user with employee_id of "123" has a password of "govols!!!"


  Scenario: Authenticate a known user with a matching password
    When I attempt to authenticate "shep_clark" with password "govols!!!"
    Then the response status code should be 200
      And the following data is returned:
        | property     | value                 |
        | employee_id  | 123                   |
        | first_name   | Shep                  |
        | last_name    | Clark                 |
        | display_name | Shep Clark            |
        | username     | shep_clark            |
        | email        | shep_clark@example.org|
        | active       | yes                   |
        | locked       | no                    |

#
#  Scenario: Attempt to create an authentication for an unknown user
#    When I realize the username does not exist in my system
#    Then I will respond negatively
#
#  Scenario: Attempt to create an authentication without a username
#  Scenario: Attempt to create an authentication with an invalid username
#
#  Scenario: Attempt to create an authentication for a known user with a mismatched password
#    When I realize the given password has does not match the stored version
#    Then I will respond negatively
#
#  Scenario: Attempt to create an authentication without a password
#  Scenario: Attempt to create an authentication for a known user with an invalid password
#
#  Scenario: Attempt to retrieve an authentication
#  Scenario: Attempt to update an authentication
#  Scenario: Attempt to delete an authentication
#  Scenario: Attempt to create an authentication
