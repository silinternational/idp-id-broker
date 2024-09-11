Feature: Syncing a specific app-prefix of external groups with an external list

  Scenario: Add an external group to a user's list for a particular app
    Given the following users exist, with these external groups:
        | email                  | groups   |
        | john_smith@example.org | wiki-one |
      And the "wiki" external groups list is the following:
        | email                  | groups            |
        | john_smith@example.org | wiki-one,wiki-two |
    When I sync the list of "wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups            |
        | john_smith@example.org | wiki-one,wiki-two |

  Scenario: Change the external group in a user's list for a particular app
    Given the following users exist, with these external groups:
        | email                  | groups   |
        | john_smith@example.org | wiki-one |
      And the "wiki" external groups list is the following:
        | email                  | groups   |
        | john_smith@example.org | wiki-two |
    When I sync the list of "wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups   |
        | john_smith@example.org | wiki-two |

  Scenario: Leave a user's external groups for a different app unchanged
    Given the following users exist, with these external groups:
        | email                  | groups              |
        | john_smith@example.org | wiki-one,map-europe |
      And the "wiki" external groups list is the following:
        | email                  | groups   |
        | john_smith@example.org | wiki-two |
    When I sync the list of "wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups               |
        | john_smith@example.org | wiki-two,map-europe |

  Scenario: Remove an external group from a user's list for a particular app
    Given the following users exist, with these external groups:
        | email                  | groups            |
        | john_smith@example.org | wiki-one,wiki-two |
      And the "wiki" external groups list is the following:
        | email                  | groups   |
        | john_smith@example.org | wiki-one |
    When I sync the list of "wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups   |
        | john_smith@example.org | wiki-one |

  Scenario: Remove all external groups from a user's list for a particular app (no entry in list)
    Given the following users exist, with these external groups:
        | email                  | groups                     |
        | john_smith@example.org | wiki-one,wiki-two,map-asia |
      And the "wiki" external groups list is the following:
        | email | groups |
    When I sync the list of "wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups   |
        | john_smith@example.org | map-asia |

  Scenario: Remove all external groups from a user's list for a particular app (blank entry in list)
    Given the following users exist, with these external groups:
        | email                  | groups                     |
        | john_smith@example.org | wiki-one,wiki-two,map-asia |
      And the "wiki" external groups list is the following:
        | email                  | groups |
        | john_smith@example.org |        |
    When I sync the list of "wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups   |
        | john_smith@example.org | map-asia |

  Scenario: Try to add an external group that does not match the given app-prefix
    Given the following users exist, with these external groups:
        | email                  | groups   |
        | john_smith@example.org | wiki-one |
      And the "wiki" external groups list is the following:
        | email                  | groups            |
        | john_smith@example.org | wiki-one,map-asia |
    When I sync the list of "wiki" external groups
    Then there should have been a sync error
      And the following users should have the following external groups:
        | email                  | groups   |
        | john_smith@example.org | wiki-one |

  Scenario: Properly handle (trim) spaces around external groups
    Given the following users exist, with these external groups:
        | email                  | groups   |
        | john_smith@example.org | wiki-one |
      And the "wiki" external groups list is the following:
        | email                  | groups             |
        | john_smith@example.org | wiki-one, wiki-two |
    When I sync the list of "wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups            |
        | john_smith@example.org | wiki-one,wiki-two |

  Scenario: Properly handle an empty email address
    Given the following users exist, with these external groups:
        | email                  | groups   |
        | john_smith@example.org | wiki-one |
      And the "wiki" external groups list is the following:
        | email                  | groups            |
        |                        | wiki-one          |
        | john_smith@example.org | wiki-one,wiki-two |
    When I sync the list of "wiki" external groups
    Then there should have been a sync error
      And the following users should have the following external groups:
        | email                  | groups            |
        | john_smith@example.org | wiki-one,wiki-two |

  # Scenario: Send 1 notification email if sync finds group(s) for a user not in this IDP
