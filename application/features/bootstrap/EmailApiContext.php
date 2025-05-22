<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Step\When;
use FeatureContext;

class EmailApiContext extends FeatureContext
{
    public const EMAIL = '/email';
    public const HTML_BODY_TEXT = 'html body';
    public const TEST_EXAMPLE_ORG = 'test@example.org';
    public const TESTBCC_EXAMPLE_ORG = 'testbcc@example.org';
    public const TESTCC_EXAMPLE_ORG = 'testcc@example.org';
    public const TEXT_BODY_TEXT = 'text body';
    public const SUBJECT_TEXT = 'subject text';
    public const TO_ADDRESS = 'to_address';
    public const CC_ADDRESS = 'cc_address';
    public const BCC_ADDRESS = 'bcc_address';
    public const SUBJECT = 'subject';
    public const HTML_BODY = 'html_body';
    public const TEXT_BODY = 'text_body';
    public const ATTEMPTS_COUNT = 'attempts_count';
    public const DELAY_SECONDS = 'delay_seconds';
    public const SEND_AFTER = 'send_after';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    #[When('we queue an email with minimum fields using a text body')]
    public function testQueue_MinimumFields_TextBody()
    {
        $this->setRequestBody(self::TO_ADDRESS, self::TEST_EXAMPLE_ORG);
        $this->setRequestBody(self::SUBJECT, 'test subject min fields (text body)');
        $this->setRequestBody(self::TEXT_BODY, self::TEXT_BODY_TEXT);
        $this->iRequestTheResourceBe(self::EMAIL, self::CREATED);
    }

    #[When('we queue an email with minimum fields using an html body')]
    public function testQueue_MinimumFields_HtmlBody()
    {
        $this->setRequestBody(self::TO_ADDRESS, self::TEST_EXAMPLE_ORG);
        $this->setRequestBody(self::SUBJECT, 'test subject min fields (text body)');
        $this->setRequestBody(self::HTML_BODY, '<p>html body</p>');
        $this->iRequestTheResourceBe(self::EMAIL, self::CREATED);
    }

    #[When('we queue an email with allowed fields, delay_seconds')]
    public function testQueue_AllowedFields_DelaySeconds()
    {
        $this->setRequestBody(self::TO_ADDRESS, self::TEST_EXAMPLE_ORG);
        $this->setRequestBody(self::CC_ADDRESS, self::TESTCC_EXAMPLE_ORG);
        $this->setRequestBody(self::BCC_ADDRESS, self::TESTBCC_EXAMPLE_ORG);
        $this->setRequestBody(self::SUBJECT, 'subject allowed fields');
        $this->setRequestBody(self::TEXT_BODY, self::TEXT_BODY_TEXT);
        $this->setRequestBody(self::HTML_BODY, self::HTML_BODY_TEXT);
        $this->setRequestBody(self::DELAY_SECONDS, 10);
        $this->iRequestTheResourceBe(self::EMAIL, self::CREATED);
    }

    #[When('we queue an email with allowed fields, send_after')]
    public function testQueue_AllowedFields_SendAfter()
    {
        $this->setRequestBody(self::TO_ADDRESS, self::TEST_EXAMPLE_ORG);
        $this->setRequestBody(self::CC_ADDRESS, self::TESTCC_EXAMPLE_ORG);
        $this->setRequestBody(self::BCC_ADDRESS, self::TESTBCC_EXAMPLE_ORG);
        $this->setRequestBody(self::SUBJECT, 'subject allowed fields');
        $this->setRequestBody(self::TEXT_BODY, self::TEXT_BODY_TEXT);
        $this->setRequestBody(self::HTML_BODY, self::HTML_BODY_TEXT);
        $this->setRequestBody(self::SEND_AFTER, 1556314645);
        $this->iRequestTheResourceBe(self::EMAIL, self::CREATED);
    }

    #[When('we queue an email with all fields')]
    public function testQueue_AllFields()
    {
        $this->setRequestBody(self::TO_ADDRESS, self::TEST_EXAMPLE_ORG);
        $this->setRequestBody(self::CC_ADDRESS, self::TESTCC_EXAMPLE_ORG);
        $this->setRequestBody(self::BCC_ADDRESS, self::TESTBCC_EXAMPLE_ORG);
        $this->setRequestBody(self::SUBJECT, 'subject all fields');
        $this->setRequestBody(self::TEXT_BODY, self::TEXT_BODY_TEXT);
        $this->setRequestBody(self::HTML_BODY, self::HTML_BODY_TEXT);
        $this->setRequestBody(self::ATTEMPTS_COUNT, 456);
        $this->setRequestBody(self::CREATED_AT, 11111111);
        $this->setRequestBody(self::UPDATED_AT, 22222222);
        $this->setRequestBody(self::SEND_AFTER, 1556314645);
        $this->iRequestTheResourceBe(self::EMAIL, self::CREATED);
    }

    #[When('we queue an email using a GET')]
    public function testInvalidMethodGet()
    {
        $this->iRequestTheResourceBe(self::EMAIL, self::RETRIEVED);
    }

    #[When('we queue an email using a DELETE')]
    public function testInvalidMethodDelete()
    {
        $this->iRequestTheResourceBe(self::EMAIL, self::DELETED);
    }

    #[When('we queue an email using a PUT')]
    public function testInvalidMethodPut()
    {
        $this->iRequestTheResourceBe(self::EMAIL, self::UPDATED);
    }

    #[When('we queue an email without the required to_address')]
    public function testQueue_RequiredFieldsMissing_ToAddress()
    {
        $this->iRequestTheResourceBe(self::EMAIL, self::CREATED);
    }

    #[When('we queue an email without the required subject')]
    public function testQueue_RequiredFieldsMissing_Subject()
    {
        $this->setRequestBody(self::TO_ADDRESS, self::TEST_EXAMPLE_ORG);
        $this->iRequestTheResourceBe(self::EMAIL, self::CREATED);
    }

    #[When('we queue an email without the required text body')]
    public function testQueue_RequiredFieldsMissing_TextBody()
    {
        $this->setRequestBody(self::TO_ADDRESS, self::TEST_EXAMPLE_ORG);
        $this->setRequestBody(self::SUBJECT, self::SUBJECT_TEXT);
        $this->iRequestTheResourceBe(self::EMAIL, self::CREATED);
    }

    #[When('we queue an email with an invalid to_address')]
    public function testQueue_InvalidToAddress()
    {
        $this->setRequestBody(self::TO_ADDRESS, 'test');
        $this->setRequestBody(self::SUBJECT, self::SUBJECT_TEXT);
        $this->setRequestBody(self::TEXT_BODY, self::TEXT_BODY_TEXT);
        $this->iRequestTheResourceBe(self::EMAIL, self::CREATED);
    }

    #[When('we queue an email with an invalid cc_address')]
    public function testQueue_InvalidCcAddress()
    {
        $this->setRequestBody(self::TO_ADDRESS, self::TEST_EXAMPLE_ORG);
        $this->setRequestBody(self::CC_ADDRESS, 'testCc');
        $this->setRequestBody(self::SUBJECT, self::SUBJECT_TEXT);
        $this->setRequestBody(self::TEXT_BODY, self::TEXT_BODY_TEXT);
        $this->iRequestTheResourceBe(self::EMAIL, self::CREATED);
    }

    #[When('we queue an email with an invalid bcc_address')]
    public function testQueue_InvalidBccAddress()
    {
        $this->setRequestBody(self::TO_ADDRESS, self::TEST_EXAMPLE_ORG);
        $this->setRequestBody(self::BCC_ADDRESS, 'testBcc');
        $this->setRequestBody(self::SUBJECT, self::SUBJECT_TEXT);
        $this->setRequestBody(self::TEXT_BODY, self::TEXT_BODY_TEXT);
        $this->iRequestTheResourceBe(self::EMAIL, self::CREATED);
    }
}
