Feature: Unit Tests for the HIBP component

  Scenario Outline: Validate returned hashes match as expected
    Given I have a <password>
    When I ask for it as hashes
    Then I'll get a <hashPrefix> and <hashSuffix>

    Examples:
      | password           | hashPrefix    | hashSuffix                           |
      | pass123            | aafdc         | 23870ecbcd3d557b6423a8982134e17927e  |
      | alk4f2355f2d45d4f  | 2084a         | 389b33471898ad8ee44b47ed98ff2856053  |


  Scenario: Validate HIBP responses for pwned password
    Given I have a pwned password
    When I ask if it is pwned
    Then I'll get a true response

  Scenario: Validate HIBP responses for not pwned password
    Given I have a random password that has not been pwned
    When I ask if it is pwned
    Then I'll get a false response