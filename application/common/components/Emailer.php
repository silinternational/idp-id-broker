<?php
namespace common\components;

use common\models\EmailLog;
use common\models\User;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Sil\EmailService\Client\EmailServiceClient;
use Sil\Psr3Adapters\Psr3Yii2Logger;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\web\ServerErrorHttpException;

class Emailer extends Component
{
    const SUBJECT_INVITE_DEFAULT = 'Your New Account';
    const SUBJECT_MFA_RATE_LIMIT_DEFAULT = 'Too Many 2-Step Verification Attempts';
    const SUBJECT_PASSWORD_CHANGED_DEFAULT = 'Your account password has been changed';
    
    /**
     * The configuration for the email-service client.
     *
     * @var array
     */
    public $emailServiceConfig = [];
    
    /** @var EmailServiceClient */
    protected $emailServiceClient = null;
    
    /** @var LoggerInterface */
    public $logger = null;
    
    /**
     * Other values that should be made available to be inserted into emails.
     * The keys should be camelCase and will be made available as variables
     * (e.g. `$camelCase`) in the emailer's view files.
     *
     * @var array<string,mixed>
     */
    public $otherDataForEmails = [];
    
    public $sendInviteEmails = false;
    public $sendMfaRateLimitEmails = true;
    public $sendPasswordChangedEmails = false;
    
    /**
     * The list of subjects, keyed on message type. This is initialized during
     * the `init()` call during construction.
     *
     * @var array
     */
    protected $subjects;
    
    public $subjectForInvite;
    public $subjectForMfaRateLimit;
    public $subjectForPasswordChanged;
    
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
        if ( ! self::isValidFormat($format)) {
            throw new \InvalidArgumentException(sprintf(
                "The email format must be 'html' or 'text', not %s.",
                var_export($format, true)
            ), 1511801775);
        }
        
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
        if ($this->logger === null) {
            $this->logger = new Psr3Yii2Logger();
        }
        
        $this->subjectForInvite = $this->subjectForInvite ?? self::SUBJECT_INVITE_DEFAULT;
        $this->subjectForMfaRateLimit = $this->subjectForMfaRateLimit ?? self::SUBJECT_MFA_RATE_LIMIT_DEFAULT;
        $this->subjectForPasswordChanged = $this->subjectForPasswordChanged ?? self::SUBJECT_PASSWORD_CHANGED_DEFAULT;
        
        $this->subjects = [
            EmailLog::MESSAGE_TYPE_INVITE => $this->subjectForInvite,
            EmailLog::MESSAGE_TYPE_MFA_RATE_LIMIT => $this->subjectForMfaRateLimit,
            EmailLog::MESSAGE_TYPE_PASSWORD_CHANGED => $this->subjectForPasswordChanged,
        ];
        
        $this->assertConfigIsValid();
        
        $this->verifyOtherDataForEmailIsValid();
        
        parent::init();
    }
    
    /**
     * Determine whether the given format string is valid.
     *
     * @param string $format
     * @return bool
     */
    protected function isValidFormat($format)
    {
        return in_array($format, ['html', 'text'], true);
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
        $dataForEmail = ArrayHelper::merge(
            $user->getAttributesForEmail(),
            $this->otherDataForEmails
        );
        
        $htmlView = $this->getViewForMessage($messageType, 'html');
        $textView = $this->getViewForMessage($messageType, 'text');
        
        $this->email(
            $user->email,
            $this->getSubjectForMessage($messageType),
            \Yii::$app->view->render($htmlView, $dataForEmail),
            \Yii::$app->view->render($textView, $dataForEmail)
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
     * Whether we should send a password-changed message to the given User.
     *
     * @param User $user The User in question.
     * @param array $changedAttributes The old values for any attributes that
     *     were changed (whereas the User object already has the new, updated
     *     values). NOTE: This will only contain entries for attributes that
     *     were changed!
     * @return bool
     */
    public function shouldSendPasswordChangedMessageTo($user, $changedAttributes)
    {
        return $this->sendPasswordChangedEmails
            && array_key_exists('current_password_id', $changedAttributes);
    }
    
    /**
     * Verify that the other data provided for use in emails is acceptable. If
     * any data is missing, log that error but let the email be sent anyway.
     * We'd rather they get an incomplete email than no email.
     */
    protected function verifyOtherDataForEmailIsValid()
    {
        foreach ($this->otherDataForEmails as $keyForEmail => $valueForEmail) {
            if (empty($valueForEmail)) {
                $this->logger->critical(sprintf(
                    'Missing a value for %s (for use in emails)',
                    $keyForEmail
                ));
                $this->otherDataForEmails[$keyForEmail] = '(MISSING)';
            }
        }
    }
}
