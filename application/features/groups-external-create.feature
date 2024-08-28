Feature: Setting a User's list of external groups when they have none (for that prefix)

  Background:
    Given the requester is authorized

  Scenario: Set the list of external groups for a user with no external groups
    Given a user exists
      And that user's list of external groups is ""
    When I set that user's list of "wiki" external groups to the following:
      | externalGroup |
      | wiki-users    |
      | wiki-managers |
    Then the response status code should be 204
      And that user's list of external groups should be "wiki-users,wiki-managers"

  Scenario: Set the list of external groups for a user with only other-prefix external groups
    Given a user exists
      And that user's list of external groups is "map-america"
    When I set that user's list of "wiki" external groups to the following:
      | externalGroup |
      | wiki-users    |
    Then the response status code should be 204
      And that user's list of external groups should be "map-america,wiki-users"
