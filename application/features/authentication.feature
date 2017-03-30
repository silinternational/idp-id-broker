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
        | employee_id  | 123                   |
        | first_name   | Shep                  |
        | last_name    | Clark                 |
        | display_name | Shep Clark            |
        | username     | shep_clark            |
        | email        | shep_clark@example.org|
        | active       | yes                   |
        | locked       | no                    |

  Scenario: Attempt to authenticate an unknown user
    Given I provide the following valid data:
        | property  | value     |
        | username  | daddy_o   |
        | password  | govols!!  |
    When I request "/authentication" be created
    Then the response status code should be 404

  Scenario: Attempt to authenticate without providing a username
    Given I provide the following valid data:
        | property  | value       |
        | username  | shep_clark  |
        | password  | govols!!    |
      And then I remove the username
    When I request "/authentication" be created
    Then the response status code should be 404

  Scenario Outline: Attempt to authenticate while providing an invalid username
    Given I provide an invalid <property> of <value>
    When I request "/authentication" be created
    Then the response status code should be 404

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
    Then the response status code should be 400
      And the property message should contain "password"

  Scenario: Attempt to authenticate without providing a password
    Given I provide the following valid data:
        | property  | value       |
        | username  | shep_clark  |
        | password  | govols!!    |
      And then I remove the password
    When I request "/authentication" be created
    Then the response status code should be 400
      And the property message should contain "Password"

  Scenario Outline: Attempt to authenticate while providing an invalid password
    Given I provide an invalid <property> of <value>
    When I request "/authentication" be created
    Then the response status code should be 400
      And the property message should contain "assword"

    Examples:
      | property | value |
      | password | ""    |
      | password | true  |
      | password | false |
      | password | null  |
      | password | 1     |
      | password | 0     |
      | password | 21    |

  Scenario Outline: Attempt to act upon an authentication in an undefined way
      And the user store is empty
    When I request "/authentication" be <action>
    Then the response status code should be 405
      And the property message should contain "not allowed"
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
