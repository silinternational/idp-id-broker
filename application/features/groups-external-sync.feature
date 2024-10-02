Feature: Syncing a specific app-prefix of external groups with an external list

  Scenario: Add an external group to a user's list for a particular app
    Given the following users exist, with these external groups:
        | email                  | groups       |
        | john_smith@example.org | ext-wiki-one |
      And the "ext-wiki" external groups list is the following:
        | email                  | groups                    |
        | john_smith@example.org | ext-wiki-one,ext-wiki-two |
    When I sync the list of "ext-wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups                    |
        | john_smith@example.org | ext-wiki-one,ext-wiki-two |

  Scenario: Change the external group in a user's list for a particular app
    Given the following users exist, with these external groups:
        | email                  | groups       |
        | john_smith@example.org | ext-wiki-one |
      And the "ext-wiki" external groups list is the following:
        | email                  | groups       |
        | john_smith@example.org | ext-wiki-two |
    When I sync the list of "ext-wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups       |
        | john_smith@example.org | ext-wiki-two |

  Scenario: Leave a user's external groups for a different app unchanged
    Given the following users exist, with these external groups:
        | email                  | groups                      |
        | john_smith@example.org | ext-wiki-one,ext-map-europe |
      And the "ext-wiki" external groups list is the following:
        | email                  | groups       |
        | john_smith@example.org | ext-wiki-two |
    When I sync the list of "ext-wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups                      |
        | john_smith@example.org | ext-wiki-two,ext-map-europe |

  Scenario: Remove an external group from a user's list for a particular app
    Given the following users exist, with these external groups:
        | email                  | groups                    |
        | john_smith@example.org | ext-wiki-one,ext-wiki-two |
      And the "ext-wiki" external groups list is the following:
        | email                  | groups       |
        | john_smith@example.org | ext-wiki-one |
    When I sync the list of "ext-wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups       |
        | john_smith@example.org | ext-wiki-one |

  Scenario: Remove all external groups from a user's list for a particular app (no entry in list)
    Given the following users exist, with these external groups:
        | email                  | groups                                 |
        | john_smith@example.org | ext-wiki-one,ext-wiki-two,ext-map-asia |
      And the "ext-wiki" external groups list is the following:
        | email | groups |
    When I sync the list of "ext-wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups       |
        | john_smith@example.org | ext-map-asia |

  Scenario: Remove all external groups from a user's list for a particular app (blank entry in list)
    Given the following users exist, with these external groups:
        | email                  | groups                                 |
        | john_smith@example.org | ext-wiki-one,ext-wiki-two,ext-map-asia |
      And the "ext-wiki" external groups list is the following:
        | email                  | groups |
        | john_smith@example.org |        |
    When I sync the list of "ext-wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups       |
        | john_smith@example.org | ext-map-asia |

  Scenario: Try to use an app-prefix that does not begin with "ext-"
    When I sync the list of "wiki" external groups
    Then there should have been a sync error that mentions "ext-"

  Scenario: Try to add an external group that does not match the given app-prefix
    Given the following users exist, with these external groups:
        | email                  | groups       |
        | john_smith@example.org | ext-wiki-one |
      And the "ext-wiki" external groups list is the following:
        | email                  | groups                    |
        | john_smith@example.org | ext-wiki-one,ext-map-asia |
    When I sync the list of "ext-wiki" external groups
    Then there should have been a sync error
      And the following users should have the following external groups:
        | email                  | groups       |
        | john_smith@example.org | ext-wiki-one |

  Scenario: Properly handle (trim) spaces around external groups
    Given the following users exist, with these external groups:
        | email                  | groups       |
        | john_smith@example.org | ext-wiki-one |
      And the "ext-wiki" external groups list is the following:
        | email                  | groups                     |
        | john_smith@example.org | ext-wiki-one, ext-wiki-two |
    When I sync the list of "ext-wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups                    |
        | john_smith@example.org | ext-wiki-one,ext-wiki-two |

  Scenario: Properly handle an empty email address
    Given the following users exist, with these external groups:
        | email                  | groups       |
        | john_smith@example.org | ext-wiki-one |
      And the "ext-wiki" external groups list is the following:
        | email                  | groups                    |
        |                        | ext-wiki-one              |
        | john_smith@example.org | ext-wiki-one,ext-wiki-two |
    When I sync the list of "ext-wiki" external groups
    Then there should have been a sync error
      And the following users should have the following external groups:
        | email                  | groups                    |
        | john_smith@example.org | ext-wiki-one,ext-wiki-two |

  Scenario: Properly handle mismatched casing (uppercase/lowercase) in an email address
    Given the following users exist, with these external groups:
        | email                  | groups       |
        | john_smith@example.org | ext-wiki-one |
        | Jane_Doe@example.org   | ext-wiki-two |
      And the "ext-wiki" external groups list is the following:
        | email                  | groups       |
        | John_smith@example.org | ext-wiki-one |
        | jane_doe@example.org   | ext-wiki-two |
    When I sync the list of "ext-wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups       |
        | john_smith@example.org | ext-wiki-one |
        | Jane_Doe@example.org   | ext-wiki-two |

  Scenario: Send 1 notification email if sync includes user(s) not in this IDP
    Given only the following users exist, with these external groups:
        | email                  | groups       |
        | john_smith@example.org | ext-wiki-one |
      And the "ext-wiki" external groups list is the following:
        | email                     | groups       |
        | jane_doe@example.org      | ext-wiki-one |
        | john_smith@example.org    | ext-wiki-two |
        | bob_mcmanager@example.org | ext-wiki-two |
      And we have provided an error-notifications email address
    When I sync the list of "ext-wiki" external groups
    Then there should have been a sync error
      And we should have sent exactly 1 "ext-wiki" sync-error notification email
      And the following users should have the following external groups:
        | email                  | groups       |
        | john_smith@example.org | ext-wiki-two |
