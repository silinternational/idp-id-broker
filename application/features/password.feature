Feature: Password
  In order to update the password of a specific user
  As an authorized user
  I need to be able to update a specific user password

  Scenario: Provide a new password for an existing user
    Given there is a user in the database
    When the user submits a new password
    Then a new password hash should be stored

  Scenario: Update the password hash cost
    Given there is a user in the database
      And that user has a password with a low hash cost
    When the user uses their password
    Then the password hash should be updated

#  Scenario: Attempt to update a password for a nonexistent user
#  Scenario: Attempt to update a password for an existing user without providing a password
#  Scenario: Attempt to update a password for an existing user without providing a valid password
#
#  Scenario: Change the password for an existing user
#    Given I receive an existing employee id
#      And the requestor is authorized
#      And the user already has a password
#      And I receive a password
#    When I receive a request to change the password for a specific user
#    Then a new password hash should be stored
#      And the last changed date should be stored as the instant it was stored
#      And the last changed date should be stored in UTC
#      And the last synched date should be stored as the instant it was stored
#      And the last synched date should be stored in UTC
#      And the previous password hash should be saved in history
#
#  Scenario: Attempt to create a password
#  Scenario: Attempt to retrieve a password
#  Scenario: Attempt to delete a password
#  Scenario: Attempt to change the password of an existing user using the same password they already have.
#  Scenario: consider invalid employee ids that test the type conversions of Yii and/or PHP, e.g., /user/[true|false|1|0]/password
#  Scenario: ensure password_hash and last_changed date are the only things that change even when passed all attributes available on the table.
# TODO: need to test the expiration date calculation
# TODO: need to test the reuse limit
# TODO: need to test grace period and grace period extension
