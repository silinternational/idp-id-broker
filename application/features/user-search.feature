Feature: User Search
  In order to find users
  As an authorized requester
  I need to be able to retrieve user information

  Background:
    Given the user store is empty
      And the requester is authorized
      And I create the following users:
        | employee_id | first_name | last_name | display_name           | username  | email                       | personal_email        |
        | 2105        | Brice      | Morar     | Brice Felton Morar     | bmorar    | brice_morar@example.org     | brice@example.com     |
        | 2106        | Rosalinda  | Morar     | Rosalinda Zieme Morar  | rmorar    | rosalinda_morar@example.org | rosalinda@example.com |
        | 2107        | Conroy     | Easterly  | Conroy Hamill Easterly | ceasterly | conroy_easterly@example.org | conroy@example.com    |

  Scenario Outline: Find a user by username
    Given I provide a username query property of "<username>"
    When I search by username
    Then the response status code should be 200
      And user <employee_id> is returned

    Examples:
      | username    | employee_id |
      | bmorar      | 2105        |
      | rmorar      | 2106        |
      | ceasterly   | 2107        |

  Scenario Outline: Find a user by email
    Given I provide an email query property of "<email>"
    When I search by email
    Then the response status code should be 200
     And user <employee_id> is returned

    Examples:
      | email                       | employee_id |
      | brice_morar@example.org     | 2105        |
      | rosalinda_morar@example.org | 2106        |
      | conroy_easterly@example.org | 2107        |

  Scenario Outline: Search for a user by multiple fields
    Given I provide a search query property of "<search>"
    When I search by search
    Then the response status code should be 200
     And user <employee_id> is returned

    Examples:
      | search  | employee_id |
      | 105     | 2105        |
      | rice    | 2105        |
      | mor     | 2105        |
      | Felton  | 2105        |
      | bmor    | 2105        |
      | rar@exa | 2105        |
      | ice@exa | 2105        |

  Scenario Outline: Search for a non-existing user
    Given I provide a "<query>" query property of "missing user"
    When I search by "<query>"
    Then the response status code should be 200
     And no users are returned

    Examples:
      | query    |
      | username |
      | email    |
      | search   |



#  TODO: Limit the columns returned
