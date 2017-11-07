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
    const SUBJECT_INVITE_DEFAULT = 'Your New Account';
    const SUBJECT_MFA_RATE_LIMIT_DEFAULT = 'Incorrect 2-Step Verification Attempts';
    const SUBJECT_WELCOME_DEFAULT = 'Welcome';
    
    /**
     * The configuration for the email-service client.
     *
     * @var array
     */
    public $emailServiceConfig = [];
    
    /** @var EmailServiceClient */
    protected $emailServiceClient = null;
    
    public $sendInviteEmails = false;
    public $sendMfaRateLimitEmails = true;
    public $sendWelcomeEmails = false;
    
    /**
     * The list of subjects, keyed on message type. This is initialized during
     * the `init()` call during construction.
     *
     * @var array
     */
    protected $subjects;
    
    public $subjectForInvite;
    public $subjectForMfaRateLimit;
    public $subjectForWelcome;
    
    /**
     * Assert that the given configuration values are acceptable.
     *
     * @throws InvalidArgumentException
     */
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
                throw new InvalidArgumentException(
                    'Missing email service configuration for ' . $param,
                    1502311757
                );
            }
        }
        
        foreach ($this->subjects as $messageType => $subject) {
            if (empty($subject)) {
                throw new InvalidArgumentException(sprintf(
                    'Subject (for %s message) cannot be empty. Given: %s',
                    var_export($messageType, true),
                    var_export($subject, true)
                ));
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
     * Set up various values, using defaults when needed, and ensure the values
     * we end up with are valid.
     */
    public function init()
    {
        $this->subjectForInvite = $this->subjectForInvite ?? self::SUBJECT_INVITE_DEFAULT;
        $this->subjectForMfaRateLimit = $this->subjectForMfaRateLimit ?? self::SUBJECT_MFA_RATE_LIMIT_DEFAULT;
        $this->subjectForWelcome = $this->subjectForWelcome ?? self::SUBJECT_WELCOME_DEFAULT;
        
        $this->subjects = [
            EmailLog::MESSAGE_TYPE_INVITE => $this->subjectForInvite,
            EmailLog::MESSAGE_TYPE_MFA_RATE_LIMIT => $this->subjectForMfaRateLimit,
            EmailLog::MESSAGE_TYPE_WELCOME => $this->subjectForWelcome,
        ];
        
        $this->assertConfigIsValid();
        
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
     * @param array $oldAttributeValues The old attribute values (whereas the
     *     User object already has the new, updated values).
     * @return bool
     */
    public function shouldSendWelcomeMessageTo($user, $oldAttributeValues)
    {
        return $this->sendWelcomeEmails
            && array_key_exists('current_password_id', $oldAttributeValues)
            && ($oldAttributeValues['current_password_id'] === null)
            && !empty($user->current_password_id)
            && !$user->hasReceivedMessage(EmailLog::MESSAGE_TYPE_WELCOME);
    }
}
