Feature: User
  In order to identify users
  As an authorized user
  I need to be able to manage user information

  Scenario: Create a new user
    Given the user does not already exist
      And the requestor is authorized
      And I receive an employee id
      And I receive a first name
      And I receive a last name
      And I receive a display name
      And I receive a username
      And I receive an email
    When I receive a request to create a user
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
      And the last synched date should be stored as the instant it was stored
      And the last synched date should be stored in UTC

  Scenario: Attempt to create a user without providing an employee id
  # TODO: there should be a scenario for each of the required properties
  Scenario: Attempt to create a user without providing a valid employee id
  # TODO: there should be a scenario for each of the updatable properties
  Scenario: Attempt to create a user with an employee id that already exists
  # TODO: there should be a scenario for each of the properties that must be unique
  Scenario: Attempt to create a user with an employee id that is too long
  # TODO: there should be a scenario for each of the properties that have a maximum length
  Scenario: Update an existing user without any changes
  Scenario: Change the employee id of an existing user
  # TODO: there should be a scenario for each of the updatable properties

  Scenario: Attempt to retrieve a user
  Scenario: Attempt to update a user
  Scenario: Attempt to delete a user
