Feature: Authentication
  In order to identify a specific user
  As an authorized requester
  I need to be able to authenticate a user based on certain credentials

  Background:
    Given the requester is authorized
      And the user store is empty
      And I provide the following valid data:
        | property     | value                 |
        | employee_id  | 93939202111           |
        | first_name   | Pwned                 |
        | last_name    | User                  |
        | display_name | Pwned User            |
        | username     | pwned_user            |
        | email        | pwned_user@example.org|
      And I request "/user" be created
      And a record exists with an employee_id of "93939202111"
      And the user has a password of "pass123"

    Given the requester is authorized

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
# TODO: check that none of the data has changed

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
    Given the user store is empty
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

  Scenario Outline: Check profile review flag on user resource in response to authenticate call
    Given there is a "shep_clark" user in the database
      And that user has a review_profile_after in the <reviewTense>
      And that user has a nag_for_mfa_after in the <mfaTense>
      And that user has a nag_for_method_after in the <methodTense>
      And I provide the following valid data:
      | property  | value       |
      | username  | shep_clark  |
      | password  | govols!!!   |
    When I request "/authentication" be created
    Then the following data is returned:
      | property       | value        |
      | employee_id    | 123          |
      | profile_review | <review>     |
      | mfa.add        | <mfaAdd>     |
      | method.add     | <methodAdd>  |

    Examples:
      | mfaTense | methodTense | reviewTense | mfaAdd | methodAdd | review |
      | past     | past        | past        | yes    | no        | no     |
      | past     | past        | future      | yes    | no        | no     |
      | past     | future      | past        | yes    | no        | no     |
      | past     | future      | future      | yes    | no        | no     |
      | future   | past        | past        | no     | yes       | no     |
      | future   | past        | future      | no     | yes       | no     |
      | future   | future      | past        | no     | no        | yes    |
      | future   | future      | future      | no     | no        | no     |

  Scenario: Correct invite code for an account with no password in the db
    Given the user "shep_clark" has no password in the database
      And the user "shep_clark" has a non-expired invite code "xyz123"
      And I provide the following valid data:
        | property  | value       |
        | invite    | xyz123      |
    When I request "/authentication" be created
    Then the response status code should be 200

  Scenario: Correct invite code for an account with a password in the db
    Given the user "shep_clark" has a non-expired invite code "xyz123"
      And I provide the following valid data:
        | property  | value       |
        | invite    | xyz123      |
    When I request "/authentication" be created
    Then the response status code should be 400

  Scenario: Correct but expired invite code for an account with no password in the db
    Given the user "shep_clark" has no password in the database
      And the user "shep_clark" has an expired invite code "xyz123"
      And I provide the following valid data:
        | property  | value       |
        | invite    | xyz123      |
    When I request "/authentication" be created
    Then the response status code should be 410

  Scenario: Incorrect invite code for an account with no password in the db
    Given the user "shep_clark" has no password in the database
      And the user "shep_clark" has a non-expired invite code "xyz123"
      And I provide the following valid data:
        | property  | value       |
        | invite    | abc123      |
    When I request "/authentication" be created
    Then the response status code should be 400

# TODO: attempt to authenticate a user who doesn't have a password yet, expect 400 (ensure timing attack protection is enforced)
# TODO: need test for check that a user's password is good all the way until midnight of the expiration/grace period dates
# TODO: need test to allow username or email address to be used for authentication

  Scenario: Authenticate a "contingent" user with an invite code
    Given I provide the following valid data:
        | property        | value                 |
        | employee_id     | 456                   |
        | first_name      | New                   |
        | last_name       | Guy                   |
        | username        | new_guy               |
        | email           | null                  |
        | personal_email  | personal@example.com  |
      And I request "/user" be created
      And the response status code should be 200
      And the user "new_guy" has a non-expired invite code "xyz123"
      And I provide the following valid data:
        | property  | value       |
        | invite    | xyz123      |
    When I request "/authentication" be created
    Then the response status code should be 200

  Scenario: Attempt to authenticate an expired "contingent" user with an invite code
    Given I provide the following valid data:
        | property        | value                 |
        | employee_id     | 456                   |
        | first_name      | New                   |
        | last_name       | Guy                   |
        | username        | new_guy               |
        | email           | null                  |
        | personal_email  | personal@example.com  |
      And I request "/user" be created
      And the response status code should be 200
      And the user "new_guy" has a non-expired invite code "xyz123"
      And the user record for "new_guy" has expired
      And I provide the following valid data:
        | property  | value       |
        | invite    | xyz123      |
    When I request "/authentication" be created
    Then the response status code should be 400

  Scenario: Attempt to authenticate a user with not pwned password
    Given I provide the following valid data:
      | property  | value        |
      | username  | shep_clark   |
      | password  | govols!!!    |
    When I request "/authentication" be created
    Then the response status code should be 200
    And The user's current password should not be marked as pwned
    And The user's password is not expired

  Scenario: Attempt to authenticate a user with pwned password
    Given I provide the following valid data:
      | property  | value        |
      | username  | pwned_user   |
      | password  | pass123      |
    When I request "/authentication" be created
    Then the response status code should be 200
    And The user's current password should be marked as pwned
    And The user's password is expired

  Scenario Outline: Successfully authenticating even if the WebAuthn MFA API is unusable
    Given "shep_clark" has a valid WebAuthn MFA method
      And I provide the following valid data:
        | property  | value        |
        | username  | shep_clark   |
        | password  | govols!!!    |
      And we have the <rightOrWrongPassword> for the WebAuthn MFA API
    When I request "/authentication" be created
    Then the response status code should be 200
      And the response body should <containPublicKeyOrNot>

    Examples:
      | rightOrWrongPassword | containPublicKeyOrNot   |
      | wrong password       | not contain "publicKey" |
      | right password       | contain "publicKey"     |
