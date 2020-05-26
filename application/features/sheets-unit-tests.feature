Feature: Unit Tests for the Google Sheets component

  Scenario: Generate a table for append to a Google Sheets document
    Given I have a list of users
      And I have an array of table headers
    When I generate a table
    Then I see that the table was generated correctly
