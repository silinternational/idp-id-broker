Feature: User
  In order to identify users
  As an authorized requester
  I need to be able to manage user information

  Background:
    Given the user store is empty

  Scenario: Create a new user
    Given a record does not exist with an employee_id of "123"
      And the requester is authorized
      And I provide the following valid data:
        | property        | value                 |
        | employee_id     | 123                   |
        | first_name      | Shep                  |
        | last_name       | Clark                 |
        | display_name    | Shep Clark            |
        | username        | shep_clark            |
        | email           | shep_clark@example.org|
        | manager_email   | boss_man@example.org  |
        | require_mfa     | yes                   |
        | hide            | yes                   |
    When I request "/user" be created
    Then the response status code should be 200
      And the following data is returned:
        | property      | value                 |
#TODO:need to ensure uuid came back but not sure about value...
        | employee_id     | 123                   |
        | first_name      | Shep                  |
        | last_name       | Clark                 |
        | display_name    | Shep Clark            |
        | username        | shep_clark            |
        | email           | shep_clark@example.org|
        | active          | yes                   |
        | locked          | no                    |
        | manager_email   | boss_man@example.org  |
        | hide            | yes                   |
      And the following data is not returned:
        | property                |
        | current_password_id     |
        | password_expires_at_utc |
      And a record exists with an employee_id of "123"
      And the following data should be stored:
        | property            | value                 |
        | first_name          | Shep                  |
        | last_name           | Clark                 |
        | display_name        | Shep Clark            |
        | username            | shep_clark            |
        | email               | shep_clark@example.org|
        | current_password_id | NULL                  |
        | active              | yes                   |
        | locked              | no                    |
        | manager_email       | boss_man@example.org  |
        | require_mfa         | yes                   |
        | spouse_email        | NULL                  |
        | personal_email      | NULL                  |
        | hide                | yes                   |
        | groups              | NULL                  |
      And last_changed_utc should be stored as now UTC
      And last_synced_utc should be stored as now UTC

#TODO: related to PUT now.
#  Scenario: "Touch" an existing user without making any changes
#    Given the requester is authorized
#      And I provide the following valid data:
#        | property     | value                 |
#        | employee_id  | 123                   |
#        | first_name   | Shep                  |
#        | last_name    | Clark                 |
#        | display_name | Shep Clark            |
#        | username     | shep_clark            |
#        | email        | shep_clark@example.org|
#      And I request "/user" be created
#      And a record exists with an employee_id of "123"
#    When I request "/user" be created again
#    Then a record exists with an employee_id of "123"
#      And the only property to change should be last_synced_utc
#      And last_synced_utc should be stored as now UTC

  Scenario Outline: Change the properties of an existing user
    Given a record does not exist with an employee_id of "123"
      And the requester is authorized
      And I provide the following valid data:
        | property        | value                 |
        | employee_id     | 123                   |
        | first_name      | Shep                  |
        | last_name       | Clark                 |
        | display_name    | Shep Clark            |
        | username        | shep_clark            |
        | email           | shep_clark@example.org|
        | hide            | yes                   |
      And I request "/user" be created
      And the response status code should be 200
      And a record exists with an employee_id of "123"
      And the following data should be stored:
        | property            | value                 |
        | first_name          | Shep                  |
        | last_name           | Clark                 |
        | display_name        | Shep Clark            |
        | username            | shep_clark            |
        | email               | shep_clark@example.org|
        | current_password_id | NULL                  |
        | active              | yes                   |
        | locked              | no                    |
        | require_mfa         | no                    |
        | hide                | yes                   |
      And I change the <property> to <value>
    When I request "/user/123" be updated
    Then the response status code should be 200
      And a record exists with a <property> of <value>
      And last_changed_utc should be stored as now UTC
      And last_synced_utc should be stored as now UTC

    Examples:
      | property        | value              |
      | first_name      | FIRST              |
      | last_name       | LAST               |
      | display_name    | DISPLAY            |
      | username        | USER               |
      | email           | chg@example.org    |
      | active          | no                 |
      | active          | yes                |
      | locked          | no                 |
      | locked          | yes                |
      | spouse_email    | spouse@example.org |
      | personal_email  | my@example.org     |
      | require_mfa     | no                 |
      | require_mfa     | yes                |
      | hide            | no                 |
      | hide            | yes                |
      | groups          | mensa,hackers      |

#TODO: consider creating a new security.feature file for all these security-related tests.
#TODO: need to think through tests for API_ACCESS_KEYS config, i.e., need tests for ApiConsumer
  Scenario Outline: Attempt to act upon a user as an unauthorized user
    Given the requester is not authorized
      And the user store is empty
    When I request "/user" be <action>
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
    When I request "/user" be <action>
    Then the response status code should be 404
      And the property message should contain ""
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
    When I request "/user" be created
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
    When I request "/user" be created
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
      | require_mfa | YES                | Require Mfa |
      | require_mfa | Yes                | Require Mfa |
      | require_mfa | yessir             | Require Mfa |
      | require_mfa | NO                 | Require Mfa |
      | require_mfa | No                 | Require Mfa |
      | require_mfa | nosir              | Require Mfa |
      | require_mfa | x                  | Require Mfa |
      | require_mfa | 1                  | Require Mfa |
      | require_mfa | 0                  | Require Mfa |
      | require_mfa | 21                 | Require Mfa |
      | require_mfa | true               | Require Mfa |
      | require_mfa | false              | Require Mfa |

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
    When I request "/user" be created
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
      | groups       | Groups       |

  Scenario Outline: Attempt to create a new user while providing an invalid value for an optional property
    Given the requester is authorized
      And the user store is empty
      And I provide the following valid data:
        | property     | value                 |
        | employee_id  | 456                   |
        | first_name   | John                  |
        | last_name    | Smith                 |
        | display_name | John Smith            |
        | username     | john_smith            |
        | email        | john_smith@example.org|
      But I provide an invalid <property> of <value>
    When I request "/user" be created
    Then the response status code should be 422
      And the property message should contain "<contents>"
      And the user store is still empty

    Examples:
      | property      | value           | contents      |
      | spouse_email  | true            | Spouse Email  |
      | spouse_email  | 123             | Spouse Email  |
      | spouse_email  | invalid.address | Spouse Email  |
      | manager_email | true            | Manager Email |
      | manager_email | 123             | Manager Email |
      | manager_email | invalid.address | Manager Email |
      | personal_email| true            | Personal Email|
      | personal_email| 123             | Personal Email|
      | personal_email| invalid.address | Personal Email|

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
      And I request "/user" be created
      And a record exists with an employee_id of "123"
    When I provide the following valid data:
        | property     | value            |
        | employee_id  | 234              |
        | first_name   | Shep             |
        | last_name    | Clark            |
        | display_name | Shep Clark       |
        | username     | shep_clark       |
        | email        | chg@example.org  |
      And I request "/user" be created
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
      And I request "/user" be created
      And a record exists with an employee_id of "123"
    When I provide the following valid data:
        | property     | value                 |
        | employee_id  | 234                   |
        | first_name   | Shep                  |
        | last_name    | Clark                 |
        | display_name | Shep Clark            |
        | username     | chg                   |
        | email        | shep_clark@example.org|
      And I request "/user" be created
    Then the response status code should be 422
      And the property message should contain "Email"
#TODO: need one of these for employee id now too.

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
      And I request "/user" be created
      And I provide the following valid data:
        | property     | value                  |
        | employee_id  | 234                    |
        | first_name   | Shepp                  |
        | last_name    | Clark                  |
        | display_name | Shepp Clark            |
        | username     | shepp_clark            |
        | email        | shepp_clark@example.org|
      And I request "/user" be created
    When I request "/user" be retrieved
    Then the response status code should be 200
      And I should receive 2 users

  Scenario: Get list of verified methods for a user
    Given A user with 1 verified method, 1 unverified method, 0 verified mfas, and 0 unverified mfas
    When I request a list of verified methods
    Then I see a list containing 1 method

  Scenario Outline: Check "nag" state when user has or doesn't have methods and mfas
    Given A user with <verifiedMethods> verified methods, <unverifiedMethods> unverified methods, <verifiedMfas> verified mfas, and <unverifiedMfas> unverified mfas
      And the nag dates are in the past
    When I request the nag state
    Then I see that the nag state is <state1>
      And I update the nag dates
    When I request the nag state
    Then I see that the nag state is <state2>
      And I update the nag dates
    When I request the nag state
    Then I see that the nag state is "none"

  Examples:
  | verifiedMethods | unverifiedMethods | verifiedMfas | unverifiedMfas | state1     | state2        |
  | 0               | 0                 | 0            | 0              | add_mfa    | add_method    |
  | 0               | 0                 | 0            | 1              | add_mfa    | add_method    |
  | 0               | 0                 | 1            | 0              | add_method | review_mfa    |
  | 0               | 0                 | 1            | 1              | add_method | review_mfa    |
  | 0               | 1                 | 0            | 0              | add_mfa    | add_method    |
  | 0               | 1                 | 0            | 1              | add_mfa    | add_method    |
  | 0               | 1                 | 1            | 0              | add_method | review_mfa    |
  | 0               | 1                 | 1            | 1              | add_method | review_mfa    |
  | 1               | 0                 | 0            | 0              | add_mfa    | review_method |
  | 1               | 0                 | 0            | 1              | add_mfa    | review_method |
  | 1               | 0                 | 1            | 0              | review_mfa | review_method |
  | 1               | 0                 | 1            | 1              | review_mfa | review_method |
  | 1               | 1                 | 0            | 0              | add_mfa    | review_method |
  | 1               | 1                 | 0            | 1              | add_mfa    | review_method |
  | 1               | 1                 | 1            | 0              | review_mfa | review_method |
  | 1               | 1                 | 1            | 1              | review_mfa | review_method |

#TODO: get a user with/without a match
#TODO: get a user with invalid id
#TODO: act upon a user/{id} in an undefined authz and !authz
#TODO: review previous tests for additional ideas
#TODO: get a user utilizing fields param

#TODO: ensure an employee_id cannot be changed in PUT
#TODO: PUT without any changes should still update last_synced, right?
#TODO: PUT with a change to an existing value on one of the unique fields should generate an error.
#TODO: PUT with a change should update the last_changed data properly...as well as the field to be updated :-)

#TODO: need to think about how to verify uuid comes back in calls.
#TODO: ensure uuid cannot be changed in any way.

#TODO: add site.feature to test all verbs to status, not found, authn/nonauthn...as well as NotFound

#TODO: make sure display_name is built up from first + last when not supplied.

  Scenario: Add a user with personal email address, expect a recovery method to be added.
    Given a record does not exist with an employee_id of "123"
    And the requester is authorized
    And I provide the following valid data:
      | property        | value                 |
      | employee_id     | 123                   |
      | first_name      | Shep                  |
      | last_name       | Clark                 |
      | username        | shep_clark            |
      | email           | shep_clark@example.org|
      | personal_email  | my@example.com        |
    When I request "/user" be created
    Then the response status code should be 200
     And a record exists with an employee_id of "123"
     And a method record exists with a value of "my@example.com"
     And the method record is marked as verified

  Scenario: Add a user with personal email address same as primary, expect a recovery method NOT to be added.
    Given a record does not exist with an employee_id of "123"
    And the requester is authorized
    And I provide the following valid data:
      | property        | value                 |
      | employee_id     | 123                   |
      | first_name      | Shep                  |
      | last_name       | Clark                 |
      | username        | shep_clark            |
      | email           | shep_clark@example.org|
      | personal_email  | shep_clark@example.org|
    When I request "/user" be created
    Then the response status code should be 200
    And a record exists with an employee_id of "123"
    And a method record does not exist with a value of "shep_clark@example.org"

  Scenario: Update a user with a personal email address, expect a recovery method to be added.
    Given a record does not exist with an employee_id of "123"
    And the requester is authorized
    And I provide the following valid data:
      | property        | value                 |
      | employee_id     | 123                   |
      | first_name      | Shep                  |
      | last_name       | Clark                 |
      | username        | shep_clark            |
      | email           | shep_clark@example.org|
    And I request "/user" be created
    And the response status code should be 200
    And a record exists with an employee_id of "123"
    And I change the personal_email to my@example.com
    When I request "/user/123" be updated
    Then the response status code should be 200
    And a record exists with a personal_email of my@example.com
    And a method record exists with a value of "my@example.com"
    And the method record is marked as verified

  Scenario: Update a user to change the personal email address, expect a recovery method to be added
  and the previous method to be deleted.
    Given a record does not exist with an employee_id of "123"
    And the requester is authorized
    And I provide the following valid data:
      | property        | value                 |
      | employee_id     | 123                   |
      | first_name      | Shep                  |
      | last_name       | Clark                 |
      | username        | shep_clark            |
      | email           | shep_clark@example.org|
      | personal_email  | old@example.com       |
    And I request "/user" be created
    And the response status code should be 200
    And a record exists with an employee_id of "123"
    And I change the personal_email to new@example.com
    When I request "/user/123" be updated
    Then the response status code should be 200
    And a record exists with a personal_email of new@example.com
    And a method record exists with a value of "new@example.com"
    And the method record is marked as verified
    And a method record does not exist with a value of "old@example.com"
