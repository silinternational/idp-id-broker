Feature: Incorporating custom (external) groups in a User's `members` list

  # Scenarios that belong here in ID Broker:

  Scenario: Include external groups in a User's `members` list
    Given a user exists
      And that user's list of groups is "one,two"
      And that user's list of external groups is "app-three,app-four"
    When I sign in as that user
    Then the members list will be "one,two,app-three,app-four"

#  Scenario: Update a user's `groups_external` list, given a group prefix and list of groups

#  # Scenarios that belong in the new "groups_external" sync:
#  Scenario: Send 1 notification email if sync finds group(s) for a user not in this IDP
#  Scenario: Add entries in the synced Google Sheet to the `groups_external` field
#  Scenario: Remove entries not in the synced Google Sheet from the `groups_external` field
#  Scenario: Only use entries from the synced Google Sheet that specify this IDP
