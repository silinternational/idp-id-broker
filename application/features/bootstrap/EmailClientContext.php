<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Hook\AfterSuite;
use Behat\Hook\BeforeScenario;
use Behat\Step\Then;
use Behat\Step\When;
use common\components\EmailClient;
use common\models\Email;
use Webmozart\Assert\Assert;
use Yii;

class EmailClientContext extends YiiContext
{
    private $stubToAddress = 'test@example.org';
    private $stubCcAddress = 'testCc@example.org';
    private $stubBccAddress = 'testBcc@example.org';
    private $stubSubject;
    private $stubTextBody = 'email content as text';
    private $stubHtmlBody = '<p>email content as html</p>';
    private $stubSendAfter = 1556312556;
    private $emailClient;

    #[BeforeScenario]
    public function _before()
    {
        Email::deleteAll();

        $this->stubSubject = 'tested at '.microtime();
        $this->emailClient = new EmailClient();
    }

    #[AfterSuite]
    public static function _after_suite()
    {
        Email::deleteAll();
        self::deleteSentEmails();
    }

    #[Then('there is no error')]
    public function thereIsNoError(): void
    {
        // This method is intentionally empty. EmailClient test scenarios were imported from
        // Codeception. Each scenario is self-contained within one context method, and the
        // "there is no error" is present simply to make the scenario read well.
    }

    #[When('we create an email by mass assignment using minimum fields for a text body')]
    public function testCreateMassAssignment_MinimumFields_TextBody()
    {
        $email = new Email();

        $email->attributes = [
            'to_address' => $this->stubToAddress,
            'subject' => $this->stubSubject,
            'text_body' => $this->stubTextBody,
        ];

        Assert::true($email->save(), current($email->getFirstErrors()));

        Assert::notNull($email->id);
        Assert::eq($this->stubToAddress, $email->to_address);
        Assert::null($email->cc_address);
        Assert::null($email->bcc_address);
        Assert::eq($this->stubSubject, $email->subject);
        Assert::eq($this->stubTextBody, $email->text_body);
        Assert::null($email->html_body);
        Assert::eq(0, $email->attempts_count);
        Assert::notNull($email->updated_at);
        Assert::notNull($email->created_at);
        Assert::null($email->send_after);
    }

    #[When('we create an email by mass assignment using minimum fields for an HTML body')]
    public function testCreateMassAssignment_MinimumFields_HtmlBody()
    {
        $email = new Email();

        $email->attributes = [
            'to_address' => $this->stubToAddress,
            'subject' => $this->stubSubject,
            'html_body' => $this->stubHtmlBody,
        ];

        Assert::true($email->save(), current($email->getFirstErrors()));

        Assert::notNull($email->id);
        Assert::eq($this->stubToAddress, $email->to_address);
        Assert::null($email->cc_address);
        Assert::null($email->bcc_address);
        Assert::eq($this->stubSubject, $email->subject);
        Assert::null($email->text_body);
        Assert::eq($this->stubHtmlBody, $email->html_body);
        Assert::eq(0, $email->attempts_count);
        Assert::notNull($email->updated_at);
        Assert::notNull($email->created_at);
        Assert::null($email->send_after);
    }

    #[When('we create an email by mass assignment using allowed fields')]
    public function testCreateMassAssignment_AllowedFields()
    {
        $email = new Email();

        $email->attributes = [
            'to_address' => $this->stubToAddress,
            'cc_address' => $this->stubCcAddress,
            'bcc_address' => $this->stubBccAddress,
            'subject' => $this->stubSubject,
            'text_body' => $this->stubTextBody,
            'html_body' => $this->stubHtmlBody,
            'send_after' => $this->stubSendAfter,
        ];

        Assert::true($email->save(), current($email->getFirstErrors()));

        Assert::notNull($email->id);
        Assert::eq($this->stubToAddress, $email->to_address);
        Assert::eq($this->stubCcAddress, $email->cc_address);
        Assert::eq($this->stubBccAddress, $email->bcc_address);
        Assert::eq($this->stubSubject, $email->subject);
        Assert::eq($this->stubTextBody, $email->text_body);
        Assert::eq($this->stubHtmlBody, $email->html_body);
        Assert::eq(0, $email->attempts_count);
        Assert::notNull($email->updated_at);
        Assert::notNull($email->created_at);
        Assert::eq($this->stubSendAfter, $email->send_after);
    }

    #[When('we create an email by mass assignment using all fields')]
    public function testCreateMassAssignment_AllFields()
    {
        $stubId = 123;
        $stubUpdateAt = 22222222;
        $stubCreatedAt = 11111111;
        $stubErrorMessage = 'stub error message';

        $email = new Email();

        $email->attributes = [
            'id' => $stubId,
            'to_address' => $this->stubToAddress,
            'cc_address' => $this->stubCcAddress,
            'bcc_address' => $this->stubBccAddress,
            'subject' => $this->stubSubject,
            'text_body' => $this->stubTextBody,
            'html_body' => $this->stubHtmlBody,
            'attempts_count' => 111,
            'updated_at' => $stubUpdateAt,
            'created_at' => $stubCreatedAt,
            'error' => $stubErrorMessage,
            'send_after' => $this->stubSendAfter,
        ];

        Assert::true($email->save(), current($email->getFirstErrors()));

        Assert::notEq($stubId, $email->id);
        Assert::eq($this->stubToAddress, $email->to_address);
        Assert::eq($this->stubCcAddress, $email->cc_address);
        Assert::eq($this->stubBccAddress, $email->bcc_address);
        Assert::eq($this->stubSubject, $email->subject);
        Assert::eq($this->stubTextBody, $email->text_body);
        Assert::eq($this->stubHtmlBody, $email->html_body);
        Assert::eq(0, $email->attempts_count);
        Assert::notEq($stubUpdateAt, $email->updated_at);
        Assert::notEq($stubCreatedAt, $email->created_at);
        Assert::eq($this->stubSendAfter, $email->send_after);
    }

    #[When('we send an email')]
    public function testSend()
    {
        $initialEmailQueueCount = Email::find()->count();
        $initialEmailSentCount = self::countMailFiles();

        $email = new Email();

        $email->attributes = [
            'to_address' => $this->stubToAddress,
            'cc_address' => $this->stubCcAddress,
            'bcc_address' => $this->stubBccAddress,
            'subject' => $this->stubSubject,
            'text_body' => $this->stubTextBody,
            'html_body' => $this->stubHtmlBody,
        ];

        Assert::true($email->save(), current($email->getFirstErrors()));

        Assert::eq($initialEmailQueueCount + 1, Email::find()->count(), 'emails in db did not increase by one after saving email');

        $n = $email->send();
        Assert::eq(1, $n, 'message not sent');

        Assert::eq($initialEmailSentCount + 1, self::countMailFiles(), 'sent emails count did not increase by one after sending email');
        Assert::eq($initialEmailQueueCount, Email::find()->count(), 'emails in db did not decrease by one after sending email');
    }

    #[When('we retry an email send')]
    public function testRetry()
    {
        $initialEmailQueueCount = Email::find()->count();
        $initialEmailSentCount = self::countMailFiles();

        $email = new Email();

        $email->attributes = [
            'to_address' => $this->stubToAddress,
            'cc_address' => $this->stubCcAddress,
            'bcc_address' => $this->stubBccAddress,
            'subject' => $this->stubSubject,
            'text_body' => $this->stubTextBody,
            'html_body' => $this->stubHtmlBody,
        ];

        Assert::true($email->save(), current($email->getFirstErrors()));

        Assert::eq($initialEmailQueueCount + 1, Email::find()->count(), 'emails in db did not increase by one after saving email');

        $email->retry();

        Assert::eq($initialEmailSentCount + 1, self::countMailFiles(), 'sent emails count did not increase by one after sending email');
        Assert::eq($initialEmailQueueCount, Email::find()->count(), 'emails in db did not decrease by one after sending email');
    }

    #[When('we get message renders as HTML and text')]
    public function testGetMessageRendersAsHtmlAndText()
    {
        $email = new Email();

        $email->attributes = [
            'to_address' => $this->stubToAddress,
            'cc_address' => $this->stubCcAddress,
            'bcc_address' => $this->stubBccAddress,
            'subject' => $this->stubSubject,
            'text_body' => $this->stubTextBody,
            'html_body' => $this->stubHtmlBody,
        ];

        Assert::true($email->save(), current($email->getFirstErrors()));

        $n = $email->send();
        Assert::eq(1, $n, 'message not sent');

        /** @var yii\mail\Message[] $sent */
        $sent = self::grabSentEmails();
        $asString = $sent[0];

        self::assertStringContainsString('text/plain', $asString);
        self::assertStringContainsString('text/html', $asString);
        self::assertStringContainsString('<!DOCTYPE html', $asString);
    }

    #[When('we send queued emails')]
    public function testSendQueuedEmails()
    {
        $initialEmailQueueCount = Email::find()->count();
        $initialEmailSentCount = self::countMailFiles();

        // create 5 queued emails
        for ($i = 0; $i < 5; $i++) {
            $email = new Email();

            $email->attributes = [
                'to_address' => $this->stubToAddress,
                'cc_address' => $this->stubCcAddress,
                'bcc_address' => $this->stubBccAddress,
                'subject' => $this->stubSubject." $i",
                'text_body' => $this->stubTextBody,
                'html_body' => $this->stubHtmlBody,
            ];

            Assert::true($email->save(), current($email->getFirstErrors()));
        }

        Assert::eq($initialEmailQueueCount + 5, Email::find()->count());

        Email::sendQueuedEmail();

        Assert::eq(0, Email::find()->count());
        Assert::eq($initialEmailSentCount + 5, self::countMailFiles());
    }

    #[When('we send delayed email')]
    public function testSendDelayedEmail()
    {
        $initialEmailQueueCount = Email::find()->count();
        $initialEmailSentCount = self::countMailFiles();

        // create 5 delayed emails
        for ($i = 0; $i < 5; $i++) {
            $email = new Email();

            $email->attributes = [
                'to_address' => $this->stubToAddress,
                'cc_address' => $this->stubCcAddress,
                'bcc_address' => $this->stubBccAddress,
                'subject' => $this->stubSubject." $i",
                'text_body' => $this->stubTextBody,
                'html_body' => $this->stubHtmlBody,
                'delay_seconds' => 2,
            ];

            Assert::true($email->save(), current($email->getFirstErrors()));
        }

        Email::sendQueuedEmail();

        Assert::eq($initialEmailQueueCount + 5, Email::find()->count());

        sleep(3);

        Email::sendQueuedEmail();

        Assert::eq(0, Email::find()->count());
        Assert::eq($initialEmailSentCount + 5, self::countMailFiles());
    }

    private static function assertStringContainsString($needle, $haystack, $message = '')
    {
        Assert::true(strpos($haystack, $needle) !== false, $message);
    }

    private static function countMailFiles()
    {
        return count(self::grabSentEmails());
    }

    private static function grabSentEmails()
    {
        $emailMessages = [];
        $files = glob(Yii::getAlias('@runtime/mail') . '/*.eml');

        if ($files === false) {
            return $emailMessages;
        }

        foreach ($files as $file) {
            $emailMessages[] = file_get_contents($file);
        }

        return $emailMessages;
    }

    private static function deleteSentEmails(): void
    {
        $files = glob(Yii::getAlias('@runtime/mail') . '/*.eml');
        if ($files !== false) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

}
