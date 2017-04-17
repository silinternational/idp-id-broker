Feature: LDAP

  Scenario: Incorrect password for an account with no password in the db, just in ldap
    Given there is a "shep_clark" user in the database with no password
      And there is a "shep_clark" user in the ldap with a password of "govols!!!"
    When I try to authenticate as "shep_clark" using "ThisIsWrong"
    Then the authentication should NOT be successful

  Scenario: Correct password for an account with no password in the db, just in ldap
    Given there is a "shep_clark" user in the database with no password
      And there is a "shep_clark" user in the ldap with a password of "govols!!!"
    When I try to authenticate as "shep_clark" using "govols!!!"
    Then the authentication SHOULD be successful

  Scenario: LDAP is offline and the given user exists in the db and has a password
    Given there is a "shep_clark" user in the database with a password of "govols!!!"
      And the LDAP is offline
    When I try to authenticate as "shep_clark" using "govols!!!"
    Then the authentication SHOULD be successful

  Scenario: LDAP is offline and the given user exists in the db but has no password
    Given there is a "shep_clark" user in the database with no password
      And there is a "shep_clark" user in the ldap with a password of "govols!!!"
      But the LDAP is offline
    When I try to authenticate as "shep_clark" using "govols!!!"
    Then the authentication should NOT be successful

  Scenario: LDAP password migration disabled
    Given there is a "shep_clark" user in the database with no password
      And there is a "shep_clark" user in the ldap with a password of "govols!!!"
      And LDAP password migration is disabled
    When I try to authenticate as "shep_clark" using "govols!!!"
    Then the authentication should NOT be successful
