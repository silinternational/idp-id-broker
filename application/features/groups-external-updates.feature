Feature: Updating a User's list of external groups

  Background:
    Given the requester is authorized

  Scenario: Add an external group to a user's list for a particular app
    Given a user exists
      And that user's list of external groups is "app-one"
    When I update that user's list of "app" external groups to the following:
      | externalGroup |
      | app-one       |
      | app-two       |
    Then the response status code should be 200
     And that user's list of external groups should be "app-one,app-two"

  # Scenario: Remove an external group from a user's list for a particular app
  # Scenario: Leave a user's external groups for a different app unchanged
  # Scenario: Try to add an external group that does not match the given app-prefix
