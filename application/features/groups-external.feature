Feature: Incorporating custom (external) groups in a User's `members` list

  Background:
    Given the requester is authorized

  Scenario: Include external groups in a User's `members` list
    Given a user exists
      And that user's list of groups is "one,two"
      And that user's list of external groups is "ext-app-three,ext-app-four"
    When I sign in as that user
    Then the response should contain a member array with only these elements:
      | element       |
      | one           |
      | two           |
      | ext-app-three |
      | ext-app-four  |
      | {idpName}     |

  Scenario: Gracefully handle an empty list of groups in a User's `members` list
    Given a user exists
      And that user's list of groups is ""
      And that user's list of external groups is "ext-app-three,ext-app-four"
    When I sign in as that user
    Then the response should contain a member array with only these elements:
      | element       |
      | ext-app-three |
      | ext-app-four  |
      | {idpName}     |

  Scenario: Gracefully handle an empty list of external groups in a User's `members` list
    Given a user exists
      And that user's list of groups is "one,two"
      And that user's list of external groups is ""
    When I sign in as that user
    Then the response should contain a member array with only these elements:
      | element   |
      | one       |
      | two       |
      | {idpName} |
