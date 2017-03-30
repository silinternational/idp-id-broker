Feature: Password
  In order to update the password of a specific user
  As an authorized user
  I need to be able to update a specific user password

#  Scenario: Provide a new password for an existing user
#    Given I receive an existing employee id
#      And the requestor is authorized
#      And the user does not already have a password
#      And I receive a password
#    When I receive a request to create a password for a specific user
#    Then a new password hash should be stored
#      And the last changed date should be stored as the instant it was stored
#      And the last changed date should be stored in UTC
#      And the last synched date should be stored as the instant it was stored
#      And the last synched date should be stored in UTC
#
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
