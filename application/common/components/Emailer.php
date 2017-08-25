<?php
namespace common\components;

use common\models\EmailLog;
use common\models\User;
use InvalidArgumentException;
use Sil\EmailService\Client\EmailServiceClient;
use Webmozart\Assert\Assert;
use yii\base\Component;
use yii\helpers\Inflector;
use yii\web\ServerErrorHttpException;

class Emailer extends Component
{
    /**
     * The configuration for the email-service client.
     *
     * @var array
     */
    public $emailServiceConfig = [];
    
    /** @var EmailServiceClient */
    protected $emailServiceClient = null;
    
    public $sendInviteEmails = false;
    public $sendWelcomeEmails = false;
    
    /**
     * The list of subjects, keyed on message type. This is initialized during
     * the `init()` call during construction.
     *
     * @var array
     */
    protected $subjects;
    
    public $subjectForInvite = 'Your New Account';
    public $subjectForWelcome = 'Welcome';
    
    protected function assertConfigIsValid()
    {
        $requiredParams = [
            'accessToken',
            'assertValidIp',
            'baseUrl',
            'validIpRanges',
        ];
        
        foreach ($requiredParams as $param) {
            if ( ! isset($this->emailServiceConfig[$param])) {
                throw new ServerErrorHttpException(
                    'Missing email service configuration for ' . $param,
                    1502311757
                );
            }
        }
    }
    
    /**
     * Use the email service to send an email.
     *
     * @param string $toAddress The recipient's email address.
     * @param string $subject The subject.
     * @param string $htmlBody The email body (as HTML).
     * @param string $textBody The email body (as plain text).
     */
    protected function email(
        string $toAddress,
        string $subject,
        string $htmlBody,
        string $textBody
    ) {
        $this->getEmailServiceClient()->email([
            'to_address' => $toAddress,
            'subject' => $subject,
            'html_body' => $htmlBody,
            'text_body' => $textBody,
        ]);
    }
    
    /**
     * @return EmailServiceClient
     */
    protected function getEmailServiceClient()
    {
        if ($this->emailServiceClient === null) {
            
            $this->emailServiceClient = new EmailServiceClient(
                $this->emailServiceConfig['baseUrl'],
                $this->emailServiceConfig['accessToken'],
                [
                    EmailServiceClient::ASSERT_VALID_IP_CONFIG => $this->emailServiceConfig['assertValidIp'],
                    EmailServiceClient::TRUSTED_IPS_CONFIG => $this->emailServiceConfig['validIpRanges'],
                ]
            );
        }
        
        return $this->emailServiceClient;
    }

    /**
     * Ping the /site/status URL, and throw an exception if there's a problem.
     *
     * @return string "OK".
     * @throws Exception
     */
    public function getSiteStatus()
    {
        return $this->getEmailServiceClient()->getSiteStatus();
    }
    
    protected function getSubjectForMessage(string $messageType)
    {
        return $this->subjects[$messageType] ?? null;
    }
    
    protected function getViewForMessage(string $messageType, string $format)
    {
        Assert::oneOf($format, ['html', 'text']);
        
        return sprintf(
            '@common/mail/%s.%s.php',
            Inflector::slug($messageType),
            $format
        );
    }
    
    /**
     * Ensure that we have the required configuration data.
     *
     * @throws ServerErrorHttpException
     */
    public function init()
    {
        $this->assertConfigIsValid();
        
        $this->subjects = [
            EmailLog::MESSAGE_TYPE_INVITE => $this->subjectForInvite,
            EmailLog::MESSAGE_TYPE_WELCOME => $this->subjectForWelcome,
        ];
        
        parent::init();
    }
    
    /**
     * Send the specified type of message to the given User.
     *
     * @param string $messageType The message type. Must be one of the
     *     EmailLog::MESSAGE_TYPE_* values.
     * @param User $user The intended recipient.
     */
    public function sendMessageTo(string $messageType, User $user)
    {
        $userAttributesForEmail = $user->getAttributesForEmail();
        $htmlView = $this->getViewForMessage($messageType, 'html');
        $textView = $this->getViewForMessage($messageType, 'text');
        
        $this->email(
            $user->email,
            $this->getSubjectForMessage($messageType),
            \Yii::$app->view->render($htmlView, $userAttributesForEmail),
            \Yii::$app->view->render($textView, $userAttributesForEmail)
        );
        
        EmailLog::logMessage($messageType, $user->id);
    }
    
    /**
     * Whether we should send an invite message to the given User.
     *
     * @param User $user The User in question.
     * @param bool $isNewUser Whether the User record was just created (insert,
     *     not update).
     * @return bool
     */
    public function shouldSendInviteMessageTo($user, $isNewUser)
    {
        return $this->sendInviteEmails
            && $isNewUser
            && !$user->hasReceivedMessage(EmailLog::MESSAGE_TYPE_INVITE);
    }
    
    /**
     * Whether we should send a welcome message to the given User.
     *
     * @param User $user The User in question.
     * @return bool
     */
    public function shouldSendWelcomeMessageTo($user)
    {
        return $this->sendWelcomeEmails
            && !$user->hasReceivedMessage(EmailLog::MESSAGE_TYPE_WELCOME);
    }
}
