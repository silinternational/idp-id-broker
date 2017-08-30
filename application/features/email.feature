Feature: Email

  Scenario: Not configured to send invite emails
    Given we are NOT configured to send invite emails
    When I create a new user
    Then an invite email should NOT have been sent to them
      And an invite email to that user should NOT have been logged

  Scenario: Not configured to send welcome emails
    Given we are NOT configured to send welcome emails
    When I create a new user
    Then a welcome email should NOT have been sent to them
      And a welcome email to that user should NOT have been logged

  Scenario: Not sending a welcome email for new users
    Given we are configured to send welcome emails
    When I create a new user
    Then a welcome email should NOT have been sent to them
      And a welcome email to that user should NOT have been logged

  Scenario: Not sending a welcome email to users that already had a password
    Given we are configured to send welcome emails
      And a user already exists
      And that user DOES have a password
      And I remove records of any emails that have been sent
    When I save changes to that user
    Then a welcome email should NOT have been sent to them
      And a welcome email to that user should NOT have been logged

#  Scenario Outline: When to send an invite email
#    @todo

#  Scenario Outline: When to send a welcome email
#    @todo

  Scenario: Sending an invite email
    Given we are configured to send invite emails
    When I create a new user
    Then an invite email should have been sent to them
      And an invite email to that user should have been logged

  Scenario: Sending a welcome email
    Given we are configured to send welcome emails
      And a user already exists
      And that user does NOT have a password
      And I remove records of any emails that have been sent
    When I give that user a password
    Then a welcome email should have been sent to them
      And a welcome email to that user should have been logged
