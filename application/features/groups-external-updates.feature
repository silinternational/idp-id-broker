Feature: Updating a User's list of external groups

  Background:
    Given the requester is authorized

  Scenario: Add an external group to a user's list for a particular app
    Given a user exists
      And that user's list of external groups is "wiki-users"
    When I update that user's list of "wiki" external groups to the following:
      | externalGroup |
      | wiki-users    |
      | wiki-managers |
    Then the response status code should be 204
      And that user's list of external groups should be "wiki-users,wiki-managers"

  Scenario: Remove an external group from a user's list for a particular app
    Given a user exists
      And that user's list of external groups is "wiki-users,wiki-managers"
    When I update that user's list of "wiki" external groups to the following:
      | externalGroup |
      | wiki-managers |
    Then the response status code should be 204
      And that user's list of external groups should be "wiki-managers"

  Scenario: Leave a user's external groups for a different app unchanged
    Given a user exists
      And that user's list of external groups is "wiki-users,map-europe"
    When I update that user's list of "map" external groups to the following:
      | externalGroup |
      | map-america   |
    Then the response status code should be 204
      And that user's list of external groups should be "wiki-users,map-america"

  Scenario: Try to add an external group that does not match the given app-prefix
    Given a user exists
      And that user's list of external groups is "wiki-users"
    When I update that user's list of "wiki" external groups to the following:
      | externalGroup |
      | map-america   |
    Then the response status code should be 422
      And the response body should contain "prefix"
      And that user's list of external groups should be "wiki-users"
