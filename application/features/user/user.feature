Feature: User
  In order to identify users
  As an authorized user
  I need to be able to manage user information

  Scenario: Create a new user
    Given the user does not already exist
      And the requester is authorized
    When I receive a request to create a user
      And I receive a valid employee id
      And I receive a valid first name
      And I receive a valid last name
      And I receive a valid display name
      And I receive a valid username
      And I receive a valid gmail
    Then a new id should be created
      And the employee id should be stored
      And the first name should be stored
      And the last name should be stored
      And the display name should be stored
      And the username should be stored
      And the email should be stored
      And the password hash should still be empty
      And active should be stored as a yes
      And locked should be stored as a no
      And the last changed date should be stored as the instant it was stored
      And the last changed date should be stored in UTC
      And the last synced date should be stored as the instant it was stored
      And the last synced date should be stored in UTC

  Scenario: Attempt to create a user without providing an employee id
  Scenario: Attempt to create a user without providing a valid employee id
  Scenario: Attempt to create a user without providing a first name
  Scenario: Attempt to create a user without providing a valid first name
  Scenario: Attempt to create a user without providing a last name
  Scenario: Attempt to create a user without providing a valid last name
  Scenario: Attempt to create a user without providing a display name
  Scenario: Attempt to create a user without providing a valid display name
  Scenario: Attempt to create a user without providing a username
  Scenario: Attempt to create a user without providing a valid username
  Scenario: Attempt to create a user without providing an email
  Scenario: Attempt to create a user without providing a valid active state
  Scenario: Attempt to create a user without providing a valid lock state

  Scenario: Attempt to create a new user with an employee id that already exists
  Scenario: Attempt to create a new user with a username that already exists
  Scenario: Attempt to create a new user with an email that already exists

  Scenario: Attempt to create a user with an employee id that is too long
  Scenario: Attempt to create a user with a first name that is too long
  Scenario: Attempt to create a user with a last name that is too long
  Scenario: Attempt to create a user with a display name that is too long
  Scenario: Attempt to create a user with a username that is too long
  Scenario: Attempt to create a user with an email that is too long

  Scenario: Update an existing user without any changes
  Scenario: Change the employee id of an existing user
  Scenario: Change the first name of an existing user
  Scenario: Change the last name of an existing user
  Scenario: Change the display name of an existing user
  Scenario: Change the username of an existing user
  Scenario: Change the email of an existing user
  Scenario: Change the active state of an existing user
  Scenario: Change the lock state of an existing user

  Scenario: Attempt to create a user as an unauthorized user

#  Scenario: Attempt to retrieve a user
#  Scenario: Attempt to update a user
#  Scenario: Attempt to delete a user
