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

  Scenario Outline:  When to send lost security key emails when there is no u2f usage
    Given we are configured <sendLostKeyEml> lost key emails
      And no mfas exist
      And a user already exists
      And a u2f mfa option <u2fExistsOrNot>
      And a backup code mfa option was used <backupUsedDaysAgo> days ago
      And a totp mfa option was used <totpUsedDaysAgo> days ago
    When I check if a lost security key email should be sent
    Then I see that a lost security key email <shouldOrNot> be sent

  Examples:
      | sendLostKeyEml | u2fExistsOrNot | backupUsedDaysAgo | totpUsedDaysAgo | shouldOrNot |
      | to send        | does NOT Exist | 2                 | 3               | should NOT  |
      | to send        | does NOT Exist | 2                 | 3               | should NOT  |
      | to send        | does Exist     | 2                 | 333             | should      |
      | to send        | does Exist     | 222               | 3               | should      |
      | NOT to send    | does Exist     | 2                 | 3               | should NOT  |

  Scenario Outline:  When to send lost security key emails when there is u2f usage
    Given we are configured <sendLostKeyEml> lost key emails
    And no mfas exist
    And a user already exists
    And a u2f mfa option was used <u2fUsedDaysAgo> days ago
    And a backup code mfa option was used <backupUsedDaysAgo> days ago
    When I check if a lost security key email should be sent
    Then I see that a lost security key email <shouldOrNot> be sent

    Examples:
      | sendLostKeyEml | u2fUsedDaysAgo | backupUsedDaysAgo | shouldOrNot |
      | to send        | 4              | 2                 | should NOT  |
      | to send        | 444            | 222               | should NOT  |
      | to send        | 444            | 2                 | should      |
      | NOT to send    | 444            | 2                 | should NOT  |