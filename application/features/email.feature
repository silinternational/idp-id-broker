Feature: Email

  Scenario: Sending an invite email
    Given we are configured to send invite emails
    When I create a new user
    Then an invite email should be sent to them
