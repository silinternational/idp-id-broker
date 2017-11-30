Feature: Email

  Scenario: Not configured to send invite emails
    Given we are NOT configured to send invite emails
    When I create a new user
    Then an "invite" email should NOT have been sent to them
      And an "invite" email to that user should NOT have been logged

  Scenario: Not configured to send password-changed emails
    Given we are NOT configured to send password-changed emails
    When I create a new user
    Then a "password-changed" email should NOT have been sent to them
      And a "password-changed" email to that user should NOT have been logged

  Scenario: Not sending a password-changed email for new users
    Given we are configured to send password-changed emails
    When I create a new user
    Then a "password-changed" email should NOT have been sent to them
      And a "password-changed" email to that user should NOT have been logged

#  Scenario Outline: When to send an invite email
#    @todo

#  Scenario Outline: When to send a password-changed email
#    @todo

  Scenario: Sending an invite email
    Given we are configured to send invite emails
    When I create a new user
    Then an "invite" email should have been sent to them
      And an "invite" email to that user should have been logged

  Scenario: Sending a password-changed email for first password
    Given we are configured to send password-changed emails
      And a user already exists
      And that user does NOT have a password
      And I remove records of any emails that have been sent
    When I give that user a password
    Then a "password-changed" email should have been sent to them
      And a "password-changed" email to that user should have been logged

  Scenario: Sending a password-changed email for subsequent password
    Given we are configured to send password-changed emails
      And a user already exists
      And that user DOES have a password
      And I remove records of any emails that have been sent
    When I change that user's password
    Then a "password-changed" email should have been sent to them
      And a "password-changed" email to that user should have been logged

  Scenario: Not sending a password-changed email when the password isn't changed
    Given we are configured to send password-changed emails
      And a user already exists
      And that user DOES have a password
      And I remove records of any emails that have been sent
    When I save changes to that user without changing the password
    Then a "password-changed" email should NOT have been sent to them
      And a "password-changed" email to that user should NOT have been logged
