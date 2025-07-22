Feature: Email API

  Background:
    Given the requester is authorized

  Scenario: Minimum fields with text body
    When we queue an email with minimum fields using a text body
    Then the response status code should be 200

  Scenario: Minimum fields with HTML body
    When we queue an email with minimum fields using an html body
    Then the response status code should be 200

  Scenario: Allowed fields with delay_seconds
    When we queue an email with allowed fields, delay_seconds
    Then the response status code should be 200

  Scenario: Allowed fields with send_after
    When we queue an email with allowed fields, send_after
    Then the response status code should be 200

  Scenario: All fields
    When we queue an email with all fields
    Then the response status code should be 200
    And the response body should contain "'to_address' => 'test@example.org'"
    And the response body should contain "'cc_address' => 'testcc@example.org'"
    And the response body should contain "'bcc_address' => 'testbcc@example.org'"
    And the response body should contain "'subject' => 'subject all fields'"
    And the response body should contain "'text_body' => 'text body'"
    And the response body should contain "'html_body' => 'html body'"
    And the response body should contain "'attempts_count' => NULL"
    And the response body should not contain "'created_at' => 11111111"
    And the response body should not contain "'updated_at' => 22222222"

  Scenario: Invalid method: Get
    When we queue an email using a GET
    Then the response status code should be 404

  Scenario: Invalid method: Delete
    When we queue an email using a DELETE
    Then the response status code should be 404

  Scenario: Invalid method: Put
    When we queue an email using a PUT
    Then the response status code should be 404

  Scenario: Required fields missing: to_address
    When we queue an email without the required to_address
    Then the response status code should be 422

  Scenario: Required fields missing: subject
    When we queue an email without the required subject
    Then the response status code should be 422

  Scenario: Required fields missing: text_body
    When we queue an email without the required text body
    Then the response status code should be 422

  Scenario: Invalid to_address
    When we queue an email with an invalid to_address
    Then the response status code should be 422

  Scenario: Invalid cc_address
    When we queue an email with an invalid cc_address
    Then the response status code should be 422

  Scenario: Invalid bcc_address
    When we queue an email with an invalid bcc_address
    Then the response status code should be 422
