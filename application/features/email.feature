Feature: Email

  Scenario: Not configured to send invite emails
    Given we are NOT configured to send invite emails
    When I create a new user
    Then an invite email should NOT have been sent to them
      And an invite email to that user should NOT have been logged

  Scenario: Not configured to send welcome emails
    Given we are NOT configured to send welcome emails
    When I create a new user
    Then an welcome email should NOT have been sent to them
      And an welcome email to that user should NOT have been logged

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
    When I create a new user
    Then a welcome email should have been sent to them
      And a welcome email to that user should have been logged
