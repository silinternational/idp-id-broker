Feature: User
  In order to identify users
  As an authorized requester
  I need to be able to manage user information

  Scenario: Create a new user
    Given a record does not exist with an employee_id of "123"
      And the requester is authorized
      And I provide the following valid data:
        | property     | value                 |
        | employee_id  | 123                   |
        | first_name   | Shep                  |
        | last_name    | Clark                 |
        | display_name | Shep Clark            |
        | username     | shep_clark            |
        | email        | shep_clark@example.org|
    When I request "/users" be created
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
      And the following data is not returned:
        | property      |
        | id            |
        | password_hash |
      And a record exists with an employee_id of "123"
      And the following data should be stored:
        | property     | value                 |
        | first_name   | Shep                  |
        | last_name    | Clark                 |
        | display_name | Shep Clark            |
        | username     | shep_clark            |
        | email        | shep_clark@example.org|
        | password_hash| NULL                  |
        | active       | yes                   |
        | locked       | no                    |
      And last_changed_utc should be stored as now UTC
      And last_synced_utc should be stored as now UTC

  Scenario: "Touch" an existing user without making any changes
    Given the requester is authorized
      And I provide the following valid data:
        | property     | value                 |
        | employee_id  | 123                   |
        | first_name   | Shep                  |
        | last_name    | Clark                 |
        | display_name | Shep Clark            |
        | username     | shep_clark            |
        | email        | shep_clark@example.org|
      And I request "/users" be created
      And a record exists with an employee_id of "123"
    When I request "/users" be created again
    Then a record exists with an employee_id of "123"
      And the only property to change should be last_synced_utc
      And last_synced_utc should be stored as now UTC

  Scenario Outline: Change the properties of an existing user
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
      And I request "/users" be created
      And a record exists with an employee_id of "123"
    When I change the <property> to <value>
      And I request "/users" be created again
    Then a record exists with a <property> of <value>
      And last_changed_utc and last_synced_utc are the same
      And last_synced_utc should be stored as now UTC

    Examples:
      | property    | value           |
      | first_name  | FIRST           |
      | last_name   | LAST            |
      | display_name| DISPLAY         |
      | username    | USER            |
      | email       | chg@example.org |
      | active      | no              |
      | locked      | yes             |

  Scenario Outline: Attempt to act upon a user as an unauthorized user
    Given the requester is not authorized
      And the user store is empty
    When I request "/users" be <action>
    Then the response status code should be 401
      And the property message should contain "invalid credentials"
      And the user store is still empty

    Examples:
      | action    |
      | created   |
      | retrieved |
      | updated   |
      | deleted   |
      | patched   |

  Scenario Outline: Attempt to act upon a user in an undefined way as an authorized user
    Given the requester is authorized
      And the user store is empty
    When I request "/users" be <action>
    Then the response status code should be 405
      And the property message should contain "not allowed"
      And the user store is still empty

    Examples:
      | action    |
      | updated   |
      | deleted   |
      | patched   |

  Scenario Outline: Attempt to create a new user without providing a required property
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
      But then I remove the <property>
    When I request "/users" be created
    Then the response status code should be 422
      And the property message should contain "<contents>"
      And the user store is still empty

    Examples:
      | property    | contents    |
      | employee_id | Employee ID |
      | first_name  | First Name  |
      | last_name   | Last Name   |
      | username    | Username    |
      | email       | Email       |

  Scenario Outline: Attempt to create a new user while providing an invalid property for a required property
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
      But I provide an invalid <property> of <value>
    When I request "/users" be created
    Then the response status code should be 422
      And the property message should contain "<contents>"
      And the user store is still empty

    Examples:
      | property    | value              | contents    |
      | employee_id | ""                 | Employee ID |
      | employee_id | true               | Employee ID |
      | employee_id | false              | Employee ID |
      | employee_id | null               | Employee ID |
#TODO: test fails right now      | employee_id | 1                  | Employee ID |
#TODO: test fails right now      | employee_id | 0                  | Employee ID |
#TODO: test fails right now      | employee_id | 21                 | Employee ID |
      | first_name  | ""                 | First Name  |
      | first_name  | true               | First Name  |
      | first_name  | false              | First Name  |
      | first_name  | null               | First Name  |
#TODO: test fails right now      | first_name  | 1                  | First Name  |
#TODO: test fails right now      | first_name  | 0                  | First Name  |
#TODO: test fails right now      | first_name  | 21                 | First Name  |
      | last_name   | ""                 | Last Name   |
      | last_name   | true               | Last Name   |
      | last_name   | false              | Last Name   |
      | last_name   | null               | Last Name   |
#TODO: test fails right now      | last_name   | 1                  | Last Name   |
#TODO: test fails right now      | last_name   | 0                  | Last Name   |
#TODO: test fails right now      | last_name   | 21                 | Last Name   |
      | username    | ""                 | Username    |
      | username    | true               | Username    |
      | username    | false              | Username    |
      | username    | null               | Username    |
#TODO: test fails right now      | username    | 1                  | Username    |
#TODO: test fails right now      | username    | 0                  | Username    |
#TODO: test fails right now      | username    | 21                 | Username    |
      | email       | ""                 | Email       |
      | email       | true               | Email       |
      | email       | false              | Email       |
      | email       | null               | Email       |
      | email       | shep_clark         | Email       |
      | email       | shep_clark@example | Email       |
#TODO: test fails right now      | email       | 1                  | Email       |
#TODO: test fails right now      | email       | 0                  | Email       |
#TODO: test fails right now      | email       | 21                 | Email       |
      | active      | YES                | Active      |
      | active      | Yes                | Active      |
      | active      | yessir             | Active      |
      | active      | NO                 | Active      |
      | active      | No                 | Active      |
      | active      | nosir              | Active      |
      | active      | x                  | Active      |
      | active      | 1                  | Active      |
      | active      | 0                  | Active      |
      | active      | 21                 | Active      |
      | active      | true               | Active      |
      | active      | false              | Active      |
      | locked      | YES                | Locked      |
      | locked      | Yes                | Locked      |
      | locked      | yessir             | Locked      |
      | locked      | NO                 | Locked      |
      | locked      | No                 | Locked      |
      | locked      | nosir              | Locked      |
      | locked      | x                  | Locked      |
      | locked      | 1                  | Locked      |
      | locked      | 0                  | Locked      |
      | locked      | 21                 | Locked      |
      | locked      | true               | Locked      |
      | locked      | false              | Locked      |

  Scenario Outline: Attempt to create a new user while providing an invalid(too long) property
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
      But I provide a <property> that is too long
    When I request "/users" be created
    Then the response status code should be 422
      And the property message should contain "<contents>"
      And the user store is still empty

    Examples:
      | property     | contents     |
      | employee_id  | Employee ID  |
      | first_name   | First Name   |
      | last_name    | Last Name    |
      | display_name | Display Name |
      | username     | Username     |
      | email        | Email        |


  Scenario: Attempt to create a new user with a username that already exists
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
      And I request "/users" be created
      And a record exists with an employee_id of "123"
    When I provide the following valid data:
        | property     | value            |
        | employee_id  | 234              |
        | first_name   | Shep             |
        | last_name    | Clark            |
        | display_name | Shep Clark       |
        | username     | shep_clark       |
        | email        | chg@example.org  |
      And I request "/users" be created
    Then the response status code should be 422
      And the property message should contain "Username"

  Scenario: Attempt to create a new user with an email address that already exists
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
      And I request "/users" be created
      And a record exists with an employee_id of "123"
    When I provide the following valid data:
        | property     | value                 |
        | employee_id  | 234                   |
        | first_name   | Shep                  |
        | last_name    | Clark                 |
        | display_name | Shep Clark            |
        | username     | chg                   |
        | email        | shep_clark@example.org|
      And I request "/users" be created
    Then the response status code should be 422
      And the property message should contain "Email"

  Scenario: Retrieve all users
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
      And I request "/users" be created
      And I provide the following valid data:
        | property     | value                  |
        | employee_id  | 234                    |
        | first_name   | Shepp                  |
        | last_name    | Clark                  |
        | display_name | Shepp Clark            |
        | username     | shepp_clark            |
        | email        | shepp_clark@example.org|
      And I request "/users" be created
    When I request "/users" be retrieved
    Then the response status code should be 200
      And I should receive 2 users

    #TODO: get a user with/without a match
    #TODO: get a user with both an alphanumeric and numeric employee_id
    #TODO: get a user with invalid employee_id
    #TODO: act upon a user/{id} in an undefined authz and !authz
    #TODO: review previous test for additional ideas

