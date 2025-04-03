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

  Scenario Outline:  When to send lost security key emails when there is no webauthn usage
    Given we are configured <sendLostKeyEml> lost key emails
      And I remove records of any emails that have been sent
      And no mfas exist
      And a user already exists
      And a verified webauthn mfa option <webauthnExistsOrNot>
      And a backup code mfa option was used <backupUsedDaysAgo> days ago
      And a totp mfa option was used <totpUsedDaysAgo> days ago
      And a "lost-security-key" email <hasOrHasNot> been sent to that user
    When I check if a lost security key email should be sent
    Then I see that a lost security key email <shouldOrNot> be sent

    Examples:
      | sendLostKeyEml | webauthnExistsOrNot | backupUsedDaysAgo | totpUsedDaysAgo | hasOrHasNot | shouldOrNot |
      | to send        | does NOT exist | 2                 | 3               | has NOT     | should NOT  |
      | to send        | does exist     | 222               | 333             | has NOT     | should NOT  |
      | to send        | does exist     | 2                 | 333             | has NOT     | should      |
      | to send        | does exist     | 222               | 3               | has NOT     | should      |
      | to send        | does exist     | 222               | 3               | has         | should NOT  |
      | NOT to send    | does exist     | 2                 | 333             | has NOT     | should NOT  |


  Scenario Outline:  When to send lost security key emails when there is webauthn usage
    Given we are configured <sendLostKeyEml> lost key emails
      And I remove records of any emails that have been sent
      And no mfas exist
      And a user already exists
      And a webauthn mfa option was used <webauthnUsedDaysAgo> days ago
      And a backup code mfa option was used <backupUsedDaysAgo> days ago
      And a "lost-security-key" email <hasOrHasNot> been sent to that user
    When I check if a lost security key email should be sent
    Then I see that a lost security key email <shouldOrNot> be sent

    Examples:
      | sendLostKeyEml | webauthnUsedDaysAgo | backupUsedDaysAgo | hasOrHasNot | shouldOrNot |
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
      And <webauthnCount> verified webauthn mfa option <webauthnExistsOrNot>
      And a totp mfa option <totpExistsOrNot>
      And a backup code mfa option <backupExistsOrNot>
      And a "get-backup-codes" email <hasOrHasNot> been sent to that user
    When I check if a get backup codes email should be sent
    Then I see that a get backup codes email <shouldOrNot> be sent

    Examples:
      | sendGetBackupCodesEml | webauthnCount | webauthnExistsOrNot | totpExistsOrNot | backupExistsOrNot | hasOrHasNot | shouldOrNot |
      | to send               | a | does NOT exist | does NOT exist  | does NOT exist    | has NOT     | should NOT  |
      | to send               | a | does exist     | does NOT exist  | does NOT exist    | has NOT     | should      |
      | to send               | 2 | does exist     | does NOT exist  | does NOT exist    | has NOT     | should NOT  |
      | to send               | a | does NOT exist | does exist      | does NOT exist    | has NOT     | should      |
      | to send               | a | does exist     | does exist      | does NOT exist    | has NOT     | should NOT  |
      | to send               | a | does exist     | does exist      | does exist        | has NOT     | should NOT  |
      | to send               | a | does NOT exist | does exist      | does exist        | has NOT     | should NOT  |
      | to send               | a | does exist     | does NOT exist  | does exist        | has NOT     | should NOT  |
      | to send               | a | does NOT exist | does NOT exist  | does exist        | has NOT     | should NOT  |
      | to send               | a | does exist     | does NOT exist  | does NOT exist    | has         | should NOT  |
      | NOT to send           | a | does NOT exist | does exist      | does NOT exist    | has NOT     | should NOT  |

  Scenario Outline: When to send mfa option added emails (after one has been added or deleted)
    Given we are configured <sendMfaOptionAddedEml> mfa option added emails
      And no mfas exist
      And a user already exists
      And a verified webauthn mfa option <webauthnExistsOrNot>
      And a totp mfa option <totpExistsOrNot>
      And the latest mfa event type was <mfaEventType>
    When I check if a mfa option added email should be sent
    Then I see that a mfa option added email <shouldOrNot> be sent

    Examples:
      | sendMfaOptionAddedEml | webauthnExistsOrNot | totpExistsOrNot | mfaEventType      | shouldOrNot |
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
      And a verified webauthn mfa option <webauthnExistsOrNot>
      And a totp mfa option <totpExistsOrNot>
      And the latest mfa event type was <mfaEventType>
    When I check if a mfa enabled email should be sent
    Then I see that a mfa enabled email <shouldOrNot> be sent

    Examples:
      | sendMfaEnabledEml     | webauthnExistsOrNot | totpExistsOrNot | mfaEventType      | shouldOrNot |
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
      And a verified webauthn mfa option <webauthnExistsOrNot>
      And a totp mfa option <totpExistsOrNot>
      And the latest mfa event type was <mfaEventType>
    When I check if a mfa option removed email should be sent
    Then I see that a mfa option removed email <shouldOrNot> be sent

    Examples:
      | sendMfaOptionRemovedEml | webauthnExistsOrNot | totpExistsOrNot | mfaEventType      | shouldOrNot |
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
      And a verified webauthn mfa option <webauthnExistsOrNot>
      And a totp mfa option <totpExistsOrNot>
      And the latest mfa event type was <mfaEventType>
    When I check if a mfa disabled email should be sent
    Then I see that a mfa disabled email <shouldOrNot> be sent

    Examples:
      | sendMfaDisabledEml | webauthnExistsOrNot | totpExistsOrNot | mfaEventType | shouldOrNot |
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
      And a verified webauthn mfa option was just deleted
    When I check if a mfa <optionRemovedOrDisabled> email should be sent
    Then I see that a mfa <optionRemovedOrDisabled> email <shouldOrNot> be sent

    Examples:
      | optionRemovedOrDisabled | shouldOrNot |
      | option removed          | should NOT  |
      | disabled                | should      |

  Scenario: When to send a mfa option removed email after an unverified webauthn mfa option has been deleted.
    Given we are configured to send mfa option removed emails
      And no mfas exist
      And a user already exists
      And a verified webauthn mfa option does exist
      And an unverified webauthn mfa option was just deleted
    When I check if a mfa option removed email should be sent
    Then I see that a mfa option removed email should not be sent

  Scenario: When to send a mfa disabled email after an unverified webauthn mfa option has been deleted.
    Given we are configured to send mfa disabled emails
      And no mfas exist
      And a user already exists
      And an unverified webauthn mfa option was just deleted
    When I check if a mfa disabled email should be sent
    Then I see that a mfa disabled email should not be sent


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
      And a webauthn mfa option was used 222 days ago
      And a backup code mfa option was used 2 days ago
      And a "lost-security-key" email has NOT been sent to that user
      And a second user exists with a totp mfa option
    When I send delayed mfa related emails
    Then I see that the first user has received a lost-security-key email
      And I see that the second user has received a get-backup-codes email

  Scenario: NOT sending delayed mfa related emails to inactive users
    Given we are configured to send lost key emails
    And we are configured to send get backup codes emails
    And I remove records of any emails that have been sent
    And no mfas exist
    And an inactive user already exists
    And a webauthn mfa option was used 222 days ago
    And a backup code mfa option was used 2 days ago
    And a "lost-security-key" email has NOT been sent to that user
    And a second inactive user exists with a totp mfa option
    When I send delayed mfa related emails
    Then I see that the first user has NOT received a lost-security-key email
    And I see that the second user has NOT received a get-backup-codes email

  Scenario: Send a recovery method verify email upon creation of the object
    Given a user already exists
      And no methods exist
      And I remove records of any emails that have been sent
    When I create a new recovery method
    Then a Method Verify email is sent to that method

  Scenario: Resend a recovery method verify email for an existing object
    Given a user already exists
      And an unverified method exists
      And I remove records of any emails that have been sent
    When I request that the verify email is resent
    Then a Method Verify email is sent to that method

  Scenario: Send a manager rescue code email after creation of manager mfa
    Given a user already exists
      And no mfas exist
      And a mfaManagerBcc email address is configured
    When I request a new manager mfa
    Then a Manager Rescue email is sent to the manager
      And the mfaManagerBcc email address is on the bcc line

  Scenario: Send a manager rescue code help email after creation of manager mfa
    Given a user already exists
      And no mfas exist
      And a mfaManagerHelpBcc email address is configured
    When I request a new manager mfa
    Then a "mfa-recovery-help" email should have been sent to them
      And the mfaManagerHelpBcc email address is on the bcc line

  Scenario: Send a recovery rescue code email after creation of recovery mfa
    Given a user already exists
    And no mfas exist
    When I request a new recovery mfa
    Then a Recovery Rescue email is sent to the recovery contact

  Scenario: Send a recovery rescue code help email after creation of recovery mfa
    Given a user already exists
    And no mfas exist
    When I request a new recovery mfa
    Then a "mfa-recovery-help" email should have been sent to them

  Scenario: Copy a user's personal email address on invite email message
    Given a specific user does NOT exist
      And I remove records of any emails that have been sent
     When that user is created with a personal email address
     Then an "invite" email should have been sent to them
      And that email should have been copied to the personal email address

  Scenario Outline:  When to send recovery method reminder email
    Given we are configured <toSendOrNot> recovery method reminder emails
      And I remove records of any emails that have been sent
      And a user already exists
      And no methods exist
      And a recovery method was created <number> days ago
      And a "method-reminder" email <hasOrHasNot> been sent to that user
    When I send recovery method reminder emails
    Then a "method-reminder" email <shouldOrNot> have been sent to them

    Examples:
      | toSendOrNot  | number | hasOrHasNot  | shouldOrNot    |
      | to send      | 3      | has NOT      | should NOT     |
      | to send      | 4      | has NOT      | should         |
      | to send      | 5      | has NOT      | should         |
      | to send      | 3      | has          | should NOT     |
      | to send      | 4      | has          | should NOT     |
      | to send      | 5      | has          | should NOT     |
      | NOT to send  | 5      | has NOT      | should NOT     |

  Scenario Outline:  When to send password expiring notice email
    Given we are configured <toSendOrNot> password expiring emails
      And I remove records of any emails that have been sent
      And no mfas exist
      And a user already exists
      And that user has a password that expires in <number> days
      And a "password-expiring" email <hasOrHasNot> been sent to that user
    When I send password expiring emails
    Then a "password-expiring" email <shouldOrNot> have been sent to them

    Examples:
      | toSendOrNot  | number  | hasOrHasNot  | shouldOrNot    |
      | to send      | -1      | has NOT      | should NOT     |
      | to send      | 0       | has NOT      | should         |
      | to send      | 13      | has NOT      | should         |
      | to send      | 14      | has NOT      | should         |
      | to send      | 15      | has NOT      | should NOT     |
      | to send      | 13      | has          | should NOT     |
      | to send      | 14      | has          | should NOT     |
      | to send      | 15      | has          | should NOT     |
      | NOT to send  | 15      | has NOT      | should NOT     |

  Scenario Outline:  When to send password expiring notice email for a user with MFA enabled
    Given we are configured <toSendOrNot> password expiring emails
    And the database has been purged
    And a user already exists
    And that user has a password that expires in <number> days
    And a totp mfa option does exist
    And a "password-expiring" email <hasOrHasNot> been sent to that user
    When I send password expiring emails
    Then a "password-expiring" email <shouldOrNot> have been sent to them

    Examples:
      | toSendOrNot | number | hasOrHasNot | shouldOrNot |
      | to send     | -1462  | has NOT     | should NOT  |
      | to send     | -1461  | has NOT     | should      |
      | to send     | -1447  | has NOT     | should      |
      | to send     | -1446  | has NOT     | should NOT  |
      | to send     | -1461  | has         | should NOT  |
      | to send     | -1447  | has         | should NOT  |
      | NOT to send | -1447  | has NOT     | should NOT  |

  Scenario Outline:  When to send password expired notice email
    Given we are configured <toSendOrNot> password expired emails
      And I remove records of any emails that have been sent
      And no mfas exist
      And a user already exists
      And that user has a password that expires in <number> days
      And a "password-expired" email <hasOrHasNot> been sent to that user
    When I send password expired emails
    Then a "password-expired" email <shouldOrNot> have been sent to them

    Examples:
      | toSendOrNot  | number | hasOrHasNot  | shouldOrNot    |
      | to send      | 1      | has NOT      | should NOT     |
      | to send      | 0      | has NOT      | should NOT     |
      | to send      | -1     | has NOT      | should         |
      | to send      | -15    | has NOT      | should         |
      | to send      | -16    | has NOT      | should NOT     |
      | to send      | 1      | has          | should NOT     |
      | to send      | 0      | has          | should NOT     |
      | to send      | -1     | has          | should NOT     |
      | NOT to send  | -1     | has NOT      | should NOT     |

  Scenario Outline: HR notification users
    Given hr notification email <isOrIsNot> set
      And the database has been purged
      And <active> user already exists
      And the user has not logged in for "<loginTime>"
    When I send abandoned user email
    Then the abandoned user email <hasOrHasNot> been sent

    Examples:
      | isOrIsNot | active      | loginTime | hasOrHasNot |
      | is NOT    | an inactive | 5 months  | has NOT     |
      | is NOT    | an inactive | 7 months  | has NOT     |
      | is NOT    | a           | 5 months  | has NOT     |
      | is NOT    | a           | 7 months  | has NOT     |
      | is        | an inactive | 5 months  | has NOT     |
      | is        | an inactive | 7 months  | has NOT     |
      | is        | a           | 5 months  | has NOT     |
      | is        | a           | 7 months  | has         |

  Scenario: Not sending HR notification emails too frequently
    Given hr notification email is set
      And the database has been purged
      And a user already exists
      And the user has not logged in for "7 months"
      And I send abandoned user email
    When I send abandoned user email again
    Then the abandoned user email has been sent 1 time

  Scenario: Not sending external-group sync-error emails too frequently
    Given the database has been purged
      And I send an external-groups sync-error email
    When I send an external-groups sync-error email again
    Then the external-groups sync-error email has been sent 1 time

  Scenario Outline: Sending external-group sync-error emails up to every 12 hours
    Given the database has been purged
      And I sent an external-groups sync-error email <hoursAgo>
    When I send an external-groups sync-error email again
    Then the external-groups sync-error email has been sent <timesSent>

    Examples:
      | hoursAgo     | timesSent |
      | 1 hour ago   | 1 time    |
      | 10 hours ago | 1 time    |
      | 12 hours ago | 2 times   |

  Scenario: Ensure no EmailLog is to both a User and a non-user address
    Given a user already exists
    When I try to log an email as sent to that User and to a non-user address
    Then an email log validation error should have occurred

  Scenario: Ensure each EmailLog is to either a User or a non-user address
    When I try to log an email as sent to neither a User nor a non-user address
    Then an email log validation error should have occurred