Feature: Getting a list of Users with external groups with a given prefix

  Background:
    Given the requester is authorized

  Scenario: Get the list of users (and their external groups) with a specific app prefix
    Given the following users exist, with these external groups:
      | email                     | groups                 |
      | john_smith@example.org    | wiki-one,map-america   |
      | jane_doe@example.org      | wiki-one,wiki-two      |
      | bob_mcmanager@example.org | map-america,map-europe |
    When I get the list of users with "wiki" external groups
    Then the response status code should be 200
      And the response body should contain only the following entries:
        | email                  | groups            |
        | john_smith@example.org | wiki-one          |
        | jane_doe@example.org   | wiki-one,wiki-two |

  # Scenario: Get the list of users (and their external groups) but provide no app prefix
