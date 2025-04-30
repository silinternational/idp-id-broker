Feature: Email Client

  Scenario: testCreateMassAssignment_MinimumFields_TextBody
    When we create an email by mass assignment using minimum fields for a text body
    Then there is no error

  Scenario: testCreateMassAssignment_MinimumFields_HtmlBody
    When we create an email by mass assignment using minimum fields for an HTML body
    Then there is no error

  Scenario: testCreateMassAssignment_AllowedFields
    When we create an email by mass assignment using allowed fields
    Then there is no error

  Scenario: testCreateMassAssignment_AllFields
    When we create an email by mass assignment using all fields
    Then there is no error

  Scenario: testSend
    When we send an email
    Then there is no error

  Scenario: testRetry
    When we retry an email send
    Then there is no error

  Scenario: testGetMessageRendersAsHtmlAndText
    When we get message renders as HTML and text
    Then there is no error

  Scenario: testSendQueuedEmails
    When we send queued emails
    Then there is no error

  Scenario: testSendDelayedEmail
    When we send delayed email
    Then there is no error
