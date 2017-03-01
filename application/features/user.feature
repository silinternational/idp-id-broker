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
    Then all the user info should be stored for later use

  Scenario: Attempt to create a new user when the user already exists
  Scenario: Retrieve user info and password metadata for a specific user
  Scenario: Update user info for a specific user
  Scenario: Deactivate a specific user
  Scenario: Disallow a specific user from authenticating, i.e., lock
  Scenario: Retrieve all users the requester is allowed to view
  Scenario: Retrieve user(s) based on username
