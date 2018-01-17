
Feature: MysqlDateTime

  Scenario: Check that a recent date is recognized as recent
    Given I say that recent is in the last 3 days
    When I ask if 3 days ago is recent
    Then I see that that date is recent

  Scenario: Check that today is recognized as recent
    Given I say that recent is in the last 3 days
    When I ask if 0 days ago is recent
    Then I see that that date is recent

  Scenario: Check that a barely old date is recognized as NOT recent
    Given I say that recent is in the last 3 days
    When I ask if 4 days ago is recent
    Then I see that that date is NOT recent

  Scenario: Check that an old date is recognized as NOT recent
    Given I say that recent is in the last 3 days
    When I ask if 24 days ago is recent
    Then I see that that date is NOT recent
