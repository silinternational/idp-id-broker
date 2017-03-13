Feature: User
  In order to identify users
  As an authorized user
  I need to be able to manage user information

  Scenario: Create a new user
    Given a user "does not exist" with "employee_id" of "123"
      And the requester "is" authorized
    When  I provide a valid "first_name" of "Shep"
      And I provide a valid "last_name" of "Clark"
      And I provide a valid "display_name" of "Shep Clark"
      And I provide a valid "username" of "shep_clark"
      And I provide a valid "email" of "shep_clark@example.org"
      And I request the user be created with an "employee_id" of "123"
    Then status code should be "200"
      And "employee_id" should be returned as "123"
      And "first_name" should be returned as "Shep"
      And "last_name" should be returned as "Clark"
      And "display_name" should be returned as "Shep Clark"
      And "username" should be returned as "shep_clark"
      And "email" should be returned as "shep_clark@example.org"
      And "id" should not be returned
      And "password_hash" should not be returned
      And "active" should be returned as "yes"
      And "locked" should be returned as "no"
      And "last_changed_utc" should be returned as now UTC
      And "last_synced_utc" should be returned as now UTC
      And "employee_id" should be stored as "123"
      And "first_name" should be stored as "Shep"
      And "last_name" should be stored as "Clark"
      And "display_name" should be stored as "Shep Clark"
      And "username" should be stored as "shep_clark"
      And "email" should be stored as "shep_clark@example.org"
      And "password_hash" should be stored as null
      And "active" should be stored as "yes"
      And "locked" should be stored as "no"
      And "last_changed_utc" should be stored as now UTC
      And "last_synced_utc" should be stored as now UTC

#  Scenario: Attempt to create a user without providing an employee id
#  Scenario: Attempt to create a user without providing a valid employee id
#  Scenario: Attempt to create a user without providing a first name
#  Scenario: Attempt to create a user without providing a valid first name
#  Scenario: Attempt to create a user without providing a last name
#  Scenario: Attempt to create a user without providing a valid last name
#  Scenario: Attempt to create a user without providing a display name
#  Scenario: Attempt to create a user without providing a valid display name
#  Scenario: Attempt to create a user without providing a username
#  Scenario: Attempt to create a user without providing a valid username
#  Scenario: Attempt to create a user without providing an email
#  Scenario: Attempt to create a user without providing a valid active state
#  Scenario: Attempt to create a user without providing a valid lock state
#
#  Scenario: Attempt to create a new user with an employee id that already exists
#  Scenario: Attempt to create a new user with a username that already exists
#  Scenario: Attempt to create a new user with an email that already exists
#
#  Scenario: Attempt to create a user with an employee id that is too long
#  Scenario: Attempt to create a user with a first name that is too long
#  Scenario: Attempt to create a user with a last name that is too long
#  Scenario: Attempt to create a user with a display name that is too long
#  Scenario: Attempt to create a user with a username that is too long
#  Scenario: Attempt to create a user with an email that is too long
#
#  Scenario: Update an existing user without any changes
#  Scenario: Change the employee id of an existing user
#  Scenario: Change the first name of an existing user
#  Scenario: Change the last name of an existing user
#  Scenario: Change the display name of an existing user
#  Scenario: Change the username of an existing user
#  Scenario: Change the email of an existing user
#  Scenario: Change the active state of an existing user
#  Scenario: Change the lock state of an existing user
#
#  Scenario: Attempt to create a user as an unauthorized user
#
#  Scenario: Attempt to retrieve a user
#  Scenario: Attempt to update a user
#  Scenario: Attempt to delete a user
