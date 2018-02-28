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
      And I remove records of any emails that have been sent
      And no mfas exist
      And a user already exists
      And a verified u2f mfa option <u2fExistsOrNot>
      And a backup code mfa option was used <backupUsedDaysAgo> days ago
      And a totp mfa option was used <totpUsedDaysAgo> days ago
      And a "lost-security-key" email <hasOrHasNot> been sent to that user
    When I check if a lost security key email should be sent
    Then I see that a lost security key email <shouldOrNot> be sent

    Examples:
      | sendLostKeyEml | u2fExistsOrNot | backupUsedDaysAgo | totpUsedDaysAgo | hasOrHasNot | shouldOrNot |
      | to send        | does NOT exist | 2                 | 3               | has NOT     | should NOT  |
      | to send        | does exist     | 222               | 333             | has NOT     | should NOT  |
      | to send        | does exist     | 2                 | 333             | has NOT     | should      |
      | to send        | does exist     | 222               | 3               | has NOT     | should      |
      | to send        | does exist     | 222               | 3               | has         | should NOT  |
      | NOT to send    | does exist     | 2                 | 333             | has NOT     | should NOT  |


  Scenario Outline:  When to send lost security key emails when there is u2f usage
    Given we are configured <sendLostKeyEml> lost key emails
      And I remove records of any emails that have been sent
      And no mfas exist
      And a user already exists
      And a u2f mfa option was used <u2fUsedDaysAgo> days ago
      And a backup code mfa option was used <backupUsedDaysAgo> days ago
      And a "lost-security-key" email <hasOrHasNot> been sent to that user
    When I check if a lost security key email should be sent
    Then I see that a lost security key email <shouldOrNot> be sent

    Examples:
      | sendLostKeyEml | u2fUsedDaysAgo | backupUsedDaysAgo | hasOrHasNot | shouldOrNot |
      | to send        | 4              | 2                 | has NOT     | should NOT  |
      | to send        | 444            | 222               | has NOT     | should NOT  |
      | to send        | 444            | 2                 | has NOT     | should      |
      | to send        | 444            | 2                 | has         | should NOT  |
      | NOT to send    | 444            | 2                 | has NOT     | should NOT  |

  Scenario Outline: When to send get backup codes emails
    Given we are configured <sendGetBackupCodesEml> get backup codes emails
      And I remove records of any emails that have been sent
      And no mfas exist
      And a user already exists
      And a verified u2f mfa option <u2fExistsOrNot>
      And a totp mfa option <totpExistsOrNot>
      And a backup code mfa option <backupExistsOrNot>
      And a "get-backup-codes" email <hasOrHasNot> been sent to that user
    When I check if a get backup codes email should be sent
    Then I see that a get backup codes email <shouldOrNot> be sent

    Examples:
      | sendGetBackupCodesEml | u2fExistsOrNot | totpExistsOrNot | backupExistsOrNot | hasOrHasNot | shouldOrNot |
      | to send               | does NOT exist | does NOT exist  | does NOT exist    | has NOT     | should NOT  |
      | to send               | does exist     | does NOT exist  | does NOT exist    | has NOT     | should      |
      | to send               | does NOT exist | does exist      | does NOT exist    | has NOT     | should      |
      | to send               | does exist     | does exist      | does NOT exist    | has NOT     | should NOT  |
      | to send               | does exist     | does exist      | does exist        | has NOT     | should NOT  |
      | to send               | does NOT exist | does exist      | does exist        | has NOT     | should NOT  |
      | to send               | does exist     | does NOT exist  | does exist        | has NOT     | should NOT  |
      | to send               | does NOT exist | does NOT exist  | does exist        | has NOT     | should NOT  |
      | to send               | does exist     | does NOT exist  | does NOT exist    | has         | should NOT  |
      | NOT to send           | does NOT exist | does exist      | does NOT exist    | has NOT     | should NOT  |

  Scenario Outline: When to send mfa option added emails (after one has been added or deleted)
    Given we are configured <sendMfaOptionAddedEml> mfa option added emails
      And no mfas exist
      And a user already exists
      And a verified u2f mfa option <u2fExistsOrNot>
      And a totp mfa option <totpExistsOrNot>
      And the latest mfa event type was <mfaEventType>
    When I check if a mfa option added email should be sent
    Then I see that a mfa option added email <shouldOrNot> be sent

    Examples:
      | sendMfaOptionAddedEml | u2fExistsOrNot | totpExistsOrNot | mfaEventType      | shouldOrNot |
      | to send               | does exist     | does NOT exist  | verify_mfa        | should NOT  |
      | to send               | does NOT exist | does exist      | verify_mfa        | should NOT  |
      | to send               | does exist     | does exist      | verify_mfa        | should      |
      | Not to send           | does exist     | does exist      | verify_mfa        | should NOT  |
      | to send               | does exist     | does exist      | delete_mfa        | should NOT  |
      | Not to send           | does exist     | does exist      | delete_mfa        | should NOT  |

  Scenario Outline: When to send mfa enabled emails (after one has been added or deleted)
    Given we are configured <sendMfaEnabledEml> mfa enabled emails
      And no mfas exist
      And a user already exists
      And a verified u2f mfa option <u2fExistsOrNot>
      And a totp mfa option <totpExistsOrNot>
      And the latest mfa event type was <mfaEventType>
    When I check if a mfa enabled email should be sent
    Then I see that a mfa enabled email <shouldOrNot> be sent

    Examples:
      | sendMfaEnabledEml     | u2fExistsOrNot | totpExistsOrNot | mfaEventType      | shouldOrNot |
      | to send               | does exist     | does NOT exist  | verify_mfa        | should      |
      | to send               | does NOT exist | does exist      | verify_mfa        | should      |
      | to send               | does exist     | does exist      | verify_mfa        | should NOT  |
      | NOT to send           | does NOT exist | does exist      | verify_mfa        | should NOT  |
      | to send               | does exist     | does NOT exist  | delete_mfa        | should NOT  |
      | NOT to send           | does exist     | does NOT exist  | delete_mfa        | should NOT  |

  Scenario Outline: When to send mfa option removed emails (after one has been added or deleted)
    Given we are configured <sendMfaOptionRemovedEml> mfa option removed emails
      And no mfas exist
      And a user already exists
      And a verified u2f mfa option <u2fExistsOrNot>
      And a totp mfa option <totpExistsOrNot>
      And the latest mfa event type was <mfaEventType>
    When I check if a mfa option removed email should be sent
    Then I see that a mfa option removed email <shouldOrNot> be sent

    Examples:
      | sendMfaOptionRemovedEml | u2fExistsOrNot | totpExistsOrNot | mfaEventType      | shouldOrNot |
      | to send                 | does exist     | does NOT exist  | delete_mfa        | should      |
      | to send                 | does NOT exist | does exist      | delete_mfa        | should      |
      | to send                 | does exist     | does exist      | delete_mfa        | should      |
      | NOT to send             | does exist     | does exist      | delete_mfa        | should NOT  |
      | to send                 | does exist     | does NOT exist  | verify_mfa        | should NOT  |
      | NOT to send             | does exist     | does exist      | verify_mfa        | should NOT  |

  Scenario Outline: When to send mfa disabled emails (after one has been added or deleted)
    Given we are configured <sendMfaDisabledEml> mfa disabled emails
    And no mfas exist
    And a user already exists
    And a verified u2f mfa option <u2fExistsOrNot>
    And a totp mfa option <totpExistsOrNot>
    And the latest mfa event type was <mfaEventType>
    When I check if a mfa disabled email should be sent
    Then I see that a mfa disabled email <shouldOrNot> be sent

    Examples:
      | sendMfaDisabledEml | u2fExistsOrNot | totpExistsOrNot | mfaEventType | shouldOrNot |
      | to send            | does exist     | does NOT exist  | delete_mfa   | should NOT  |
      | to send            | does NOT exist | does exist      | delete_mfa   | should NOT  |
      | to send            | does exist     | does exist      | delete_mfa   | should NOT  |
      | NOT to send        | does NOT exist | does NOT exist  | delete_mfa   | should NOT  |
      | to send            | does NOT exist | does NOT exist  | verify_mfa   | should NOT  |
      | NOT to send        | does NOT exist | does NOT exist  | verify_mfa   | should NOT  |


  Scenario Outline: What kind of email to send after the last verified mfa option has been deleted.
    Given we are configured to send mfa disabled emails
      And we are configured to send mfa option removed emails
      And no mfas exist
      And a user already exists
      And a verified u2f mfa option was just deleted
    When I check if a mfa <optionRemovedOrDisabled> email should be sent
    Then I see that a mfa <optionRemovedOrDisabled> email <shouldOrNot> be sent

    Examples:
      | optionRemovedOrDisabled | shouldOrNot |
      | option removed          | should NOT  |
      | disabled                | should      |

  Scenario: When to send an email after an unverified u2f mfa option has been deleted.
    Given we are configured to send mfa option removed emails
      And no mfas exist
      And a user already exists
      And a verified u2f mfa option does exist
      And an unverified u2f mfa option was just deleted
    When I check if a mfa option removed email should be sent
    Then I see that a mfa option removed email should not be sent


  Scenario Outline: When to send refresh backup codes emails
    Given we are configured <sendRefreshBackupCodesEml> refresh backup codes emails
      And no mfas exist
      And a user already exists
      And a backup code mfa option does exist
      And there are <backupCodes> backup codes
      And I remove records of any emails that have been sent
    When a backup code is used up by that user
    Then a "refresh-backup-codes" email <shouldOrNot> have been sent to them
      And a "refresh-backup-codes" email to that user <shouldOrNot> have been logged

    Examples:
      | sendRefreshBackupCodesEml | backupCodes    | shouldOrNot |
      | to send                   | 9              | should NOT  |
      | to send                   | 5              | should NOT  |
      | to send                   | 4              | should      |
      | to send                   | 1              | should      |
      | NOT to send               | 5              | should NOT  |
      | NOT to send               | 4              | should NOT  |

  Scenario Outline: Check if an email type has been sent recently (negative days ago means not sent yet)
    Given I remove records of any emails that have been sent
      And a user already exists
      And a "get-backup-codes" email was sent <sentDaysAgo> days ago to that user
      And a "lost-security-key" email has been sent to that user
    When I check if a get backup codes email has been sent recently
    Then I see that a get backup codes email <checkHasOrHasNot> been sent recently

    Examples:
      | sentDaysAgo | checkHasOrHasNot |
      | -1          | has NOT          |
      | 0           | has              |
      | 10          | has              |
      | 66          | has NOT          |

  Scenario: Sending delayed mfa related emails to all appropriate users
    Given we are configured to send lost key emails
      And we are configured to send get backup codes emails
      And I remove records of any emails that have been sent
      And no mfas exist
      And a user already exists
      And a u2f mfa option was used 222 days ago
      And a backup code mfa option was used 2 days ago
      And a "lost-security-key" email has NOT been sent to that user
      And a second user exists with a totp mfa option
    When I send delayed mfa related emails
    Then I see that the first user has received a lost-security-key email
      And I see that the second user has received a get-backup-codes email