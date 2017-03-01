Feature: Password
  In order to update the password of a specific user
  As an authorized user
  I need to be able to update a specific user password

  Scenario: Change password
  Given an employee id
  And a password
  When I save to the database
  Then the password hash column is updated in the database for that specific user
