Feature: Email

  Scenario Outline: When to send invite emails
    Given we are configured <sendInviteEml> invite emails
      And a specific user <userExistsOrNot>
      And that user <hasPwOrNot> a password
      And I remove records of any emails that have been sent
    When that user <userAction>
    Then an "invite" email <shouldOrNot> have been sent to them
      And an "invite" email to that user <shouldOrNot> have been logged

    Examples:
        | sendInviteEml | userExistsOrNot | hasPwOrNot    | userAction         | shouldOrNot |
        | to send       | does NOT exist  | does NOT have | is created         | should      |
        | to send       | already exists  | does NOT have | gets a password    | should NOT  |
        | to send       | already exists  | does NOT have | has non-pw changes | should NOT  |
        | to send       | already exists  | has           | gets a password    | should NOT  |
        | to send       | already exists  | has           | has non-pw changes | should NOT  |
        | NOT to send   | does NOT exist  | does NOT have | is created         | should NOT  |
        | NOT to send   | already exists  | does NOT have | gets a password    | should NOT  |
        | NOT to send   | already exists  | does NOT have | has non-pw changes | should NOT  |
        | NOT to send   | already exists  | has           | gets a password    | should NOT  |
        | NOT to send   | already exists  | has           | has non-pw changes | should NOT  |

  Scenario Outline: When to send password-changed emails
    Given we are configured <sendPwChgEml> password-changed emails
      And a specific user <userExistsOrNot>
      And that user <hasPwOrNot> a password
      And I remove records of any emails that have been sent
    When that user <userAction>
    Then a "password-changed" email <shouldOrNot> have been sent to them
      And a "password-changed" email to that user <shouldOrNot> have been logged

    Examples:
        | sendPwChgEml | userExistsOrNot | hasPwOrNot    | userAction         | shouldOrNot |
        | to send      | does NOT exist  | does NOT have | is created         | should NOT  |
        | to send      | already exists  | does NOT have | gets a password    | should NOT  |
        | to send      | already exists  | does NOT have | has non-pw changes | should NOT  |
        | to send      | already exists  | has           | gets a password    | should      |
        | to send      | already exists  | has           | has non-pw changes | should NOT  |
        | NOT to send  | does NOT exist  | does NOT have | is created         | should NOT  |
        | NOT to send  | already exists  | does NOT have | gets a password    | should NOT  |
        | NOT to send  | already exists  | does NOT have | has non-pw changes | should NOT  |
        | NOT to send  | already exists  | has           | gets a password    | should NOT  |
        | NOT to send  | already exists  | has           | has non-pw changes | should NOT  |

  Scenario Outline: When to send welcome emails
    Given we are configured <sendWelcomeEml> welcome emails
      And a specific user <userExistsOrNot>
      And that user <hasPwOrNot> a password
      And I remove records of any emails that have been sent
    When that user <userAction>
    Then a "welcome" email <shouldOrNot> have been sent to them
      And a "welcome" email to that user <shouldOrNot> have been logged

    Examples:
        | sendWelcomeEml | userExistsOrNot | hasPwOrNot    | userAction         | shouldOrNot |
        | to send        | does NOT exist  | does NOT have | is created         | should NOT  |
        | to send        | already exists  | does NOT have | gets a password    | should      |
        | to send        | already exists  | does NOT have | has non-pw changes | should NOT  |
        | to send        | already exists  | has           | gets a password    | should NOT  |
        | to send        | already exists  | has           | has non-pw changes | should NOT  |
        | NOT to send    | does NOT exist  | does NOT have | is created         | should NOT  |
        | NOT to send    | already exists  | does NOT have | gets a password    | should NOT  |
        | NOT to send    | already exists  | does NOT have | has non-pw changes | should NOT  |
        | NOT to send    | already exists  | has           | gets a password    | should NOT  |
        | NOT to send    | already exists  | has           | has non-pw changes | should NOT  |

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
