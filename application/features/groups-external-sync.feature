Feature: Syncing a specific app-prefix of external groups with an external list

  Scenario: Add an external group to a user's list for a particular app
    Given the following users exist, with these external groups:
        | email                  | groups               |
        | john_smith@example.org | wiki-one,map-america |
      And the "wiki" external groups list is the following:
        | email                  | groups            |
        | john_smith@example.org | wiki-one,wiki-two |
    When I sync the list of "wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups                        |
        | john_smith@example.org | wiki-one,map-america,wiki-two |

  Scenario: Remove an external group from a user's list for a particular app
    Given the following users exist, with these external groups:
        | email                  | groups                            |
        | john_smith@example.org | wiki-users,wiki-managers,map-asia |
      And the "wiki" external groups list is the following:
        | email                  | groups     |
        | john_smith@example.org | wiki-users |
    When I sync the list of "wiki" external groups
    Then there should not have been any sync errors
      And the following users should have the following external groups:
        | email                  | groups              |
        | john_smith@example.org | wiki-users,map-asia |
