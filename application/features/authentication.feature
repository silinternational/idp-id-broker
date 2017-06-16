Feature: Authentication
  In order to identify a specific user
  As an authorized requester
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
      And I request "/user" be created
      And a record exists with an employee_id of "123"
      And the user has a password of "govols!!!"

  Scenario: Authenticate a known user with a matching password
    Given I provide the following valid data:
        | property  | value       |
        | username  | shep_clark  |
        | password  | govols!!!   |
    When I request "/authentication" be created
    Then the response status code should be 200
      And the following data is returned:
        | property     | value                 |
#TODO:need to ensure uuid came back but not sure about value...
        | employee_id  | 123                   |
        | first_name   | Shep                  |
        | last_name    | Clark                 |
        | display_name | Shep Clark            |
        | username     | shep_clark            |
        | email        | shep_clark@example.org|
        | active       | yes                   |
        | locked       | no                    |
      And a record still exists with an employee_id of "123"
      And none of the data has changed

  Scenario: Attempt to authenticate an unknown user
    Given I provide the following valid data:
        | property  | value     |
        | username  | daddy_o   |
        | password  | govols!!  |
    When I request "/authentication" be created
    Then the authentication is not successful

  Scenario: Attempt to authenticate without providing a username
    Given I provide the following valid data:
        | property  | value       |
        | username  | shep_clark  |
        | password  | govols!!    |
      And then I remove the username
    When I request "/authentication" be created
    Then the authentication is not successful

  Scenario Outline: Attempt to authenticate while providing an invalid username
    Given I provide an invalid <property> of <value>
    When I request "/authentication" be created
    Then the authentication is not successful

    Examples:
        | property | value |
        | username | ""    |
        | username | true  |
        | username | false |
        | username | null  |
        | username | 1     |
        | username | 0     |
        | username | 21    |

  Scenario: Attempt to authenticate a known user with a mismatched password
    Given I provide the following valid data:
        | property  | value      |
        | username  | shep_clark |
        | password  | govols     |
    When I request "/authentication" be created
    Then the authentication is not successful

  Scenario: Attempt to authenticate without providing a password
    Given I provide the following valid data:
        | property  | value       |
        | username  | shep_clark  |
        | password  | govols!!    |
      And then I remove the password
    When I request "/authentication" be created
    Then the authentication is not successful

  Scenario Outline: Attempt to authenticate while providing an invalid password
    Given I provide an invalid <property> of <value>
    When I request "/authentication" be created
    Then the authentication is not successful

    Examples:
        | property | value |
        | password | ""    |
        | password | true  |
        | password | false |
        | password | null  |
        | password | 1     |
        | password | 0     |
        | password | 21    |

  Scenario Outline: Attempt to authenticate a user who's account is not in a good account status
    Given I provide the following valid data:
        | property  | value       |
        | username  | shep_clark  |
        | password  | govols!!!   |
        | active    | yes         |
        | locked    | no          |
      And the <property> is stored as <value>
    When I request "/authentication" be created
    Then the authentication is not successful
#TODO: and it isn't any faster than if the account had been in a good status, i.e., timing attack prevention
#    1. fire off 10 successful authn calls
#    2. stats_standard_deviation with those times to establish a standard deviation
#    3. make authn calls where account is either inactive or locked
#    4. ensure the time of those calls stays within the standard deviation

    Examples:
        | property | value |
        | active   | no    |
        | locked   | yes   |

#TODO: need test(s) for expired passwords

  Scenario Outline: Attempt to act upon an authentication in an undefined way
      And the user store is empty
    When I request "/authentication" be <action>
    Then the response status code should be 404
      And the property message should contain ""
      And the user store is still empty

    Examples:
        | action    |
        | retrieved |
        | updated   |
        | deleted   |
        | patched   |

  Scenario Outline: Attempt to act upon an authentication as an unauthorized user
    Given the requester is not authorized
      And the user store is empty
    When I request "/authentication" be <action>
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

  Scenario: Incorrect password for an account with no password in the db, just in ldap
    Given there is a "shep_clark" user in the ldap with a password of "govols!!!"
      And the user "shep_clark" has no password in the database
      And I provide the following valid data:
        | property  | value       |
        | username  | shep_clark  |
        | password  | ThisIsWrong |
    When I request "/authentication" be created
    Then the authentication is not successful

  Scenario: Correct password for an account with no password in the db, just in ldap
    Given there is a "shep_clark" user in the ldap with a password of "govols!!!"
      And the user "shep_clark" has no password in the database
      And I provide the following valid data:
        | property  | value       |
        | username  | shep_clark  |
        | password  | govols!!!   |
    When I request "/authentication" be created
    Then the response status code should be 200

#    TODO: attempt to authenticate a user who doesn't have a password yet, expect 400 (ensure timing attack protection is enforced)
# TODO: need test for check that a user's password is good all the way until midnight of the expiration/grace period dates
# TODO: need test to allow username or email address to be used for authentication
