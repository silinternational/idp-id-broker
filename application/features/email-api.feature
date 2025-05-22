Feature: Email API

  Background:
    Given the requester is authorized

  Scenario: Minimum Fields with Text Body
    When we queue an email with minimum fields using a text body
    Then the response status code should be 200

  Scenario: Minimum Fields with HTML Body
    When we queue an email with minimum fields using an html body
    Then the response status code should be 200

  Scenario: Allowed Fields with delay_seconds
    When we queue an email with allowed fields, delay_seconds
    Then the response status code should be 200

  Scenario: Allowed Fields with send_after
    When we queue an email with allowed fields, send_after
    Then the response status code should be 200

  Scenario: All Fields
    When we queue an email with all fields
    Then the response status code should be 200
    And the response body should not contain 456
    And the response body should not contain 11111111
    And the response body should not contain 22222222
    Then the response status code should be 200

  Scenario: Invalid Method: Get
    When we queue an email using a GET
    Then the response status code should be 404

  Scenario: Invalid Method: Delete
    When we queue an email using a DELETE
    Then the response status code should be 404

  Scenario: Invalid Method: Put
    When we queue an email using a PUT
    Then the response status code should be 404

  Scenario: Required Fields Missing: ToAddress
    When we queue an email without the required to_address
    Then the response status code should be 422

  Scenario: Required Fields Missing: Subject
    When we queue an email without the required subject
    Then the response status code should be 422

  Scenario: Required Fields Missing: TextBody
    When we queue an email without the required text body
    Then the response status code should be 422

  Scenario: Invalid To Address
    When we queue an email with an invalid to_address
    Then the response status code should be 422

  Scenario: Invalid Cc Address
    When we queue an email with an invalid cc_address
    Then the response status code should be 422

  Scenario: Invalid Bcc Address
    When we queue an email with an invalid bcc_address
    Then the response status code should be 422
