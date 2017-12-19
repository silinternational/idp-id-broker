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
      | to send        | does NOT exist | 2                 | 3               | should NOT  |
      | to send        | does exist     | 222               | 333             | should NOT  |
      | to send        | does exist     | 2                 | 333             | should      |
      | to send        | does exist     | 222               | 3               | should      |
      | NOT to send    | does exist     | 2                 | 3               | should NOT  |

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

  Scenario Outline: When to send get backup codes emails
    Given we are configured <sendGetBackupCodesEml> get backup codes emails
      And no mfas exist
      And a user already exists
      And a u2f mfa option <u2fExistsOrNot>
      And a totp mfa option <totpExistsOrNot>
      And a backup code mfa option <backupExistsOrNot>
    When I check if a get backup codes email should be sent
    Then I see that a get backup codes email <shouldOrNot> be sent

    Examples:
      | sendGetBackupCodesEml | u2fExistsOrNot | totpExistsOrNot | backupExistsOrNot | shouldOrNot |
      | to send               | does NOT exist | does NOT exist  | does NOT exist    | should NOT  |
      | to send               | does exist     | does NOT exist  | does NOT exist    | should      |
      | to send               | does NOT exist | does exist      | does NOT exist    | should      |
      | to send               | does exist     | does exist      | does NOT exist    | should NOT  |
      | to send               | does exist     | does exist      | does exist        | should NOT  |
      | to send               | does NOT exist | does exist      | does exist        | should NOT  |
      | to send               | does exist     | does NOT exist  | does exist        | should NOT  |
      | to send               | does NOT exist | does NOT exist  | does exist        | should NOT  |
      | NOT to send           | does exist     | does exist      | does NOT exist    | should NOT  |

  Scenario Outline:  When to send refresh backup codes emails
    Given we are configured <sendRefreshBackupCodesEml> refresh backup codes emails
      And no mfas exist
      And a user already exists
      And a backup code mfa option <backupExistsOrNot>
      And there are <backupCodes> backup codes
    When I check if a refresh backup codes email should be sent
    Then I see that a refresh backup codes email <shouldOrNot> be sent

    Examples:
      | sendRefreshBackupCodesEml | backupExistsOrNot | backupCodes | shouldOrNot |
      | to send                   | does NOT exist    | 0           | should NOT  |
      | to send                   | does NOT exist    | 0           | should NOT  |
      | to send                   | does exist        | 3           | should      |
      | to send                   | does exist        | 4           | should NOT  |
      | NOT to send               | does exist        | 2           | should NOT  |

  Scenario Outline: When to send mfa option added emails
    Given we are configured <sendMfaOptionAddedEml> mfa option added emails
      And no mfas exist
      And a user already exists
      And a u2f mfa option <u2fExistsOrNot>
      And a totp mfa option <totpExistsOrNot>
      And the mfa event type is set to <mfaEventType>
    When I check if a mfa option added email should be sent
    Then I see that a mfa option added email <shouldOrNot> be sent

    Examples:
      | sendMfaOptionAddedEml | u2fExistsOrNot | totpExistsOrNot | mfaEventType      | shouldOrNot |
      | to send               | does exist     | does NOT exist  | create_mfa        | should NOT  |
      | to send               | does NOT exist | does exist      | create_mfa        | should NOT  |
      | to send               | does exist     | does exist      | create_mfa        | should      |
      | Not to send           | does exist     | does exist      | create_mfa        | should NOT  |
      | to send               | does exist     | does exist      | delete_mfa        | should NOT  |
      | Not to send           | does exist     | does exist      | delete_mfa        | should NOT  |

  Scenario Outline: When to send mfa enabled emails
    Given we are configured <sendMfaEnabledEml> mfa enabled emails
    And no mfas exist
    And a user already exists
    And a u2f mfa option <u2fExistsOrNot>
    And a totp mfa option <totpExistsOrNot>
    And the mfa event type is set to <mfaEventType>
    When I check if a mfa enabled email should be sent
    Then I see that a mfa enabled email <shouldOrNot> be sent

    Examples:
      | sendMfaEnabledEml     | u2fExistsOrNot | totpExistsOrNot | mfaEventType      | shouldOrNot |
      | to send               | does exist     | does NOT exist  | create_mfa        | should      |
      | to send               | does NOT exist | does exist      | create_mfa        | should      |
      | to send               | does exist     | does exist      | create_mfa        | should NOT  |
      | Not to send           | does NOT exist | does exist      | create_mfa        | should NOT  |
      | to send               | does exist     | does NOT exist  | delete_mfa        | should NOT  |
      | Not to send           | does exist     | does NOT exist  | delete_mfa        | should NOT  |

  Scenario Outline: When to send mfa option removed emails
    Given we are configured <sendMfaOptionRemovedEml> mfa option removed emails
    And no mfas exist
    And a user already exists
    And a u2f mfa option <u2fExistsOrNot>
    And a totp mfa option <totpExistsOrNot>
    And the mfa event type is set to <mfaEventType>
    When I check if a mfa option removed email should be sent
    Then I see that a mfa option removed email <shouldOrNot> be sent

    Examples:
      | sendMfaOptionRemovedEml | u2fExistsOrNot | totpExistsOrNot | mfaEventType      | shouldOrNot |
      | to send                 | does exist     | does NOT exist  | delete_mfa        | should      |
      | to send                 | does NOT exist | does exist      | delete_mfa        | should      |
      | to send                 | does exist     | does exist      | delete_mfa        | should      |
      | to send                 | does NOT exist | does NOT exist  | delete_mfa        | should NOT  |
      | Not to send             | does exist     | does exist      | delete_mfa        | should NOT  |
      | to send                 | does exist     | does NOT exist  | create_mfa        | should NOT  |
      | Not to send             | does exist     | does exist      | create_mfa        | should NOT  |

  Scenario Outline: When to send mfa disabled emails
    Given we are configured <sendMfaDisabledEml> mfa disabled emails
    And no mfas exist
    And a user already exists
    And a u2f mfa option <u2fExistsOrNot>
    And a totp mfa option <totpExistsOrNot>
    And the mfa event type is set to <mfaEventType>
    When I check if a mfa disabled email should be sent
    Then I see that a mfa disabled email <shouldOrNot> be sent

    Examples:
      | sendMfaDisabledEml      | u2fExistsOrNot | totpExistsOrNot | mfaEventType      | shouldOrNot |
      | to send                 | does exist     | does NOT exist  | delete_mfa        | should NOT  |
      | to send                 | does NOT exist | does exist      | delete_mfa        | should NOT  |
      | to send                 | does exist     | does exist      | delete_mfa        | should NOT  |
      | to send                 | does NOT exist | does NOT exist  | delete_mfa        | should      |
      | Not to send             | does NOT exist | does NOT exist  | delete_mfa        | should NOT  |
      | to send                 | does NOT exist | does NOT exist  | create_mfa        | should NOT  |
      | Not to send             | does NOT exist | does NOT exist  | create_mfa        | should NOT  |