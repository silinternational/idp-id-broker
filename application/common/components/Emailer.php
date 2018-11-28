<?php
namespace common\components;

use common\helpers\MySqlDateTime;
use common\models\EmailLog;
use common\models\Mfa;
use common\models\User;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Sil\EmailService\Client\EmailServiceClient;
use Sil\Psr3Adapters\Psr3Yii2Logger;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\web\ServerErrorHttpException;
use yii\db\Query;

class Emailer extends Component
{
    const SUBJECT_INVITE_DEFAULT = 'Your new %s Identity account';
    const SUBJECT_MFA_RATE_LIMIT_DEFAULT = 'Too many 2-Step Verification attempts on your %s Identity account';
    const SUBJECT_PASSWORD_CHANGED_DEFAULT = 'Your %s Identity account password has been changed';
    const SUBJECT_WELCOME_DEFAULT = 'Welcome to your new %s Identity account';

    const SUBJECT_GET_BACKUP_CODES_DEFAULT = 'Get printable codes for your %s account';
    const SUBJECT_REFRESH_BACKUP_CODES_DEFAULT = 'Get a new set of printable codes for your %s account';
    const SUBJECT_LOST_SECURITY_KEY_DEFAULT = 'Have you lost the security key you use with your %s account';

    const SUBJECT_MFA_OPTION_ADDED_DEFAULT = 'A 2-Step Verification option was added to your %s account';
    const SUBJECT_MFA_OPTION_REMOVED_DEFAULT = 'A 2-Step Verification option was removed from your %s account';
    const SUBJECT_MFA_ENABLED_DEFAULT = '2-Step Verification was enabled on your %s account';
    const SUBJECT_MFA_DISABLED_DEFAULT = '2-Step Verification was disabled on your %s account';
    const SUBJECT_METHOD_VERIFY_DEFAULT = 'Please verify your new password recovery method';

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


    /**
     * Configs to say whether we should ever send certain emails
     * These get loaded automatically from common/config/main.php ['components']['emailer']
     */
    public $sendInviteEmails = false;
    public $sendMfaRateLimitEmails = true;
    public $sendPasswordChangedEmails = true;
    public $sendWelcomeEmails = true;

    public $sendGetBackupCodesEmails = true;
    public $sendRefreshBackupCodesEmails = true;
    public $sendLostSecurityKeyEmails = true;

    public $sendMfaOptionAddedEmails = true;
    public $sendMfaOptionRemovedEmails = true;
    public $sendMfaEnabledEmails = true;
    public $sendMfaDisabledEmails = true;
    
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
    public $subjectForWelcome;

    public $subjectForGetBackupCodes;
    public $subjectForRefreshBackupCodes;
    public $subjectForLostSecurityKey;

    public $subjectForMfaOptionAdded;
    public $subjectForMfaOptionRemoved;
    public $subjectForMfaEnabled;
    public $subjectForMfaDisabled;

    public $subjectForMethodVerify;

    /* The number of days of not using a security key after which we email the user */
    public $lostSecurityKeyEmailDays;

    /* Nag the user if they have FEWER than this number of backup codes */
    public $minimumBackupCodesBeforeNag;

    /* Don't resend the same type of email to the same user for X days */
    public $emailRepeatDelayDays;

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

    /**
     * @param string $messageType
     * @return null|string
     */
    protected function getSubjectForMessage(string $messageType)
    {
        if ( ! empty($this->subjects[$messageType]) && strpos($this->subjects[$messageType], '%') !== false) {
            return sprintf($this->subjects[$messageType], $this->otherDataForEmails['idpDisplayName'] ?? '');
        }
        return $this->subjects[$messageType] ?? null;
    }

    /**
     * Retrieve the view text for the given message type and format. The view is read from a
     * file in common/mail: e.g. mfa-disabled.html.php
     * @param string $messageType Message type -- should be defined in EmailLog getMessageTypes().
     * @param string $format Message format -- must be either 'text' or 'html'
     * @return string
     */
    protected function getViewForMessage(string $messageType, string $format): string
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
        $this->subjectForWelcome = $this->subjectForWelcome ?? self::SUBJECT_WELCOME_DEFAULT;

        $this->subjectForGetBackupCodes = $this->subjectForGetBackupCodes ?? self::SUBJECT_GET_BACKUP_CODES_DEFAULT;
        $this->subjectForRefreshBackupCodes = $this->subjectForRefreshBackupCodes ?? self::SUBJECT_REFRESH_BACKUP_CODES_DEFAULT;
        $this->subjectForLostSecurityKey = $this->subjectForLostSecurityKey ?? self::SUBJECT_LOST_SECURITY_KEY_DEFAULT;

        $this->subjectForMfaOptionAdded = $this->subjectForMfaOptionAdded ?? self::SUBJECT_MFA_OPTION_ADDED_DEFAULT;
        $this->subjectForMfaOptionRemoved = $this->subjectForMfaOptionRemoved ?? self::SUBJECT_MFA_OPTION_REMOVED_DEFAULT;
        $this->subjectForMfaEnabled = $this->subjectForMfaEnabled ?? self::SUBJECT_MFA_ENABLED_DEFAULT;
        $this->subjectForMfaDisabled = $this->subjectForMfaDisabled ?? self::SUBJECT_MFA_DISABLED_DEFAULT;

        $this->subjectForMethodVerify = $this->subjectForMethodVerify ?? self::SUBJECT_METHOD_VERIFY_DEFAULT;

        $this->subjects = [
            EmailLog::MESSAGE_TYPE_INVITE => $this->subjectForInvite,
            EmailLog::MESSAGE_TYPE_MFA_RATE_LIMIT => $this->subjectForMfaRateLimit,
            EmailLog::MESSAGE_TYPE_PASSWORD_CHANGED => $this->subjectForPasswordChanged,
            EmailLog::MESSAGE_TYPE_WELCOME => $this->subjectForWelcome,
            EmailLog::MESSAGE_TYPE_GET_BACKUP_CODES => $this->subjectForGetBackupCodes,
            EmailLog::MESSAGE_TYPE_REFRESH_BACKUP_CODES => $this->subjectForRefreshBackupCodes,
            EmailLog::MESSAGE_TYPE_LOST_SECURITY_KEY => $this->subjectForLostSecurityKey,
            EmailLog::MESSAGE_TYPE_MFA_OPTION_ADDED => $this->subjectForMfaOptionAdded,
            EmailLog::MESSAGE_TYPE_MFA_OPTION_REMOVED => $this->subjectForMfaOptionRemoved,
            EmailLog::MESSAGE_TYPE_MFA_ENABLED => $this->subjectForMfaEnabled,
            EmailLog::MESSAGE_TYPE_MFA_DISABLED => $this->subjectForMfaDisabled,
            EmailLog::MESSAGE_TYPE_METHOD_VERIFY => $this->subjectForMethodVerify,
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
     * @param string[] $data  Data fields for email template. Include key 'toAddress' to override
     *     sending to primary address in User object.
     */
    public function sendMessageTo(string $messageType, User $user, array $data = [])
    {
        $dataForEmail = ArrayHelper::merge(
            $user->getAttributesForEmail(),
            $this->otherDataForEmails,
            $data
        );
        
        $htmlView = $this->getViewForMessage($messageType, 'html');
        $textView = $this->getViewForMessage($messageType, 'text');
        
        $this->email(
            $data['toAddress'] ?? $user->email,
            $this->getSubjectForMessage($messageType),
            \Yii::$app->view->render($htmlView, $dataForEmail),
            \Yii::$app->view->render($textView, $dataForEmail)
        );
        
        EmailLog::logMessage($messageType, $user->id);
    }

    /**
     * Iterates over all users and sends get-backup-code and/or lost-security-key emails as is appropriate
     */
    public function sendDelayedMfaRelatedEmails()
    {
        $query = (new Query)->from('user');

        // iterate over one user at a time.
        foreach ($query->each() as $userData) {
            $user = User::findOne($userData['id']);

            if ($this->shouldSendGetBackupCodesMessageTo($user)) {
                $this->sendMessageTo(EmailLog::MESSAGE_TYPE_GET_BACKUP_CODES, $user);
            }
            if ($this->shouldSendLostSecurityKeyMessageTo($user)) {
                $this->sendMessageTo(EmailLog::MESSAGE_TYPE_LOST_SECURITY_KEY, $user);
            }
        }
    }


    /**
     *
     * Whether the user has already been sent this type of email in the last X days
     *
     * @param int $userId
     * @param string $messageType
     * @return bool
     */
    public function hasReceivedMessageRecently($userId, string $messageType)
    {

        $latestEmail = EmailLog::find()->where(['user_id' => $userId, 'message_type' =>$messageType])
            ->orderBy('sent_utc DESC')->one();
        if (empty($latestEmail)) {
            return false;
        }

        return MySqlDateTime::dateIsRecent($latestEmail->sent_utc, $this->emailRepeatDelayDays);
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
            && array_key_exists('current_password_id', $changedAttributes)
            && !is_null($changedAttributes['current_password_id']);
    }
    
    /**
     * Whether we should send a welcome message to the given User.
     *
     * @param User $user The User in question.
     * @param array $changedAttributes The old values for any attributes that
     *     were changed (whereas the User object already has the new, updated
     *     values). NOTE: This will only contain entries for attributes that
     *     were changed!
     * @return bool
     */
    public function shouldSendWelcomeMessageTo($user, $changedAttributes)
    {
        return $this->sendWelcomeEmails
            && array_key_exists('current_password_id', $changedAttributes)
            && is_null($changedAttributes['current_password_id']);
    }

    /**
     * @param User $user The User in question.
     * @return bool
     */
    public function shouldSendGetBackupCodesMessageTo($user)
    {
        return $this->sendGetBackupCodesEmails
            && count($user->getVerifiedMfaOptions()) == 1
            && ! $user->hasMfaBackupCodes()
            && ! $this->hasReceivedMessageRecently($user->id, EmailLog::MESSAGE_TYPE_GET_BACKUP_CODES);
    }

    /**
     * @param int $backupCodeCount - the number of backup codes left for the user (after a deletion)
     * @return bool
     */
    public function shouldSendRefreshBackupCodesMessage($backupCodeCount)
    {
        return $this->sendRefreshBackupCodesEmails
            && $backupCodeCount < $this->minimumBackupCodesBeforeNag;
    }

    /**
     * If a TOTP or backup code was used in the last X days but not a U2f option
     *   and if we are configured to send this sort of email, then
     *   send it.
     *
     * @param User $user
     * @return bool
     */
    public function shouldSendLostSecurityKeyMessageTo($user)
    {
        if ( ! $this->sendLostSecurityKeyEmails) {
            return false;
        }

        if ($this->hasReceivedMessageRecently($user->id, EmailLog::MESSAGE_TYPE_LOST_SECURITY_KEY)) {
            return false;
        }

        $hasU2fOption = false;
        $lastOtherUseDate = null;
        $mfaOptions = $user->getVerifiedMfaOptions();

        $recentDays = $this->lostSecurityKeyEmailDays;

        // Get the dates of the last use of the MFA options
        foreach ($mfaOptions as $mfaOption) {

            // If this is a U2F and it was used recently, don't send an email.
            if ($mfaOption->type === Mfa::TYPE_U2F) {
                $hasU2fOption = true;
                if ( ! empty($mfaOption->last_used_utc) && MySqlDateTime::dateIsRecent($mfaOption->last_used_utc, $recentDays)) {
                    return false;
                }

            // If one of the other MFA options has been used recently, remember it.
            } else if ($lastOtherUseDate === null && ! empty($mfaOption->last_used_utc)) {
                $dateIsRecent = MySqlDateTime::dateIsRecent($mfaOption->last_used_utc, $recentDays);
                $lastOtherUseDate = $dateIsRecent ? $mfaOption->last_used_utc : null;
            }
        }

        // If they don't even have a u2f option, don't send an email
        if ( ! $hasU2fOption) {
            return false;
        }

        // If a totp or backup code was used in the last X days (but not the u2f option), send an email
        if ($lastOtherUseDate !== null) {
            return true;
        }

        return false;
    }

    /**
     * Whether the user has just added an MFA option and there was already one.
     *
     * @param User $user (assumes the user instance has already been refreshed)
     * @param string Mfa::EVENT_TYPE_*
     * @return bool
     */
    public function shouldSendMfaOptionAddedMessageTo($user, $mfaEventType)
    {
        return $this->sendMfaOptionAddedEmails
            && $mfaEventType === Mfa::EVENT_TYPE_VERIFY
            && count($user->getVerifiedMfaOptions()) > 1;
    }

    /**
     * Whether the user has just added an MFA option and that's the only one they have.
     *
     * @param User $user (assumes the user instance has already been refreshed)
     * @param string Mfa::EVENT_TYPE_*
     * @return bool
     */
    public function shouldSendMfaEnabledMessageTo($user, $mfaEventType)
    {
        return $this->sendMfaEnabledEmails
            && $mfaEventType === Mfa::EVENT_TYPE_VERIFY
            && count($user->getVerifiedMfaOptions()) == 1;
    }

    /**
     * Whether the user just deleted a verified MFA option and there is still one or more left
     *
     * @param User $user (assumes the user instance has already been refreshed)
     * @param string Mfa::EVENT_TYPE_*
     * @param Mfa $mfa
     * @return bool
     */
    public function shouldSendMfaOptionRemovedMessageTo($user, $mfaEventType, $mfa)
    {
        return $this->sendMfaOptionRemovedEmails
            && $mfaEventType === Mfa::EVENT_TYPE_DELETE
            && $mfa->verified
            && count($user->getVerifiedMfaOptions()) > 0;
    }

    /**
     * Whether the user has just deleted the last verified MFA option
     *
     * @param User $user (assumes the user instance has already been refreshed)
     * @param string Mfa::EVENT_TYPE_*
     * @param Mfa $mfa
     * @return bool
     */
    public function shouldSendMfaDisabledMessageTo($user, $mfaEventType, $mfa)
    {
        return $this->sendMfaDisabledEmails
            && $mfaEventType === Mfa::EVENT_TYPE_DELETE
            && $mfa->verified
            && count($user->getVerifiedMfaOptions()) < 1;
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
