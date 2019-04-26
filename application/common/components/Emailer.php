<?php
namespace common\components;

use common\helpers\MySqlDateTime;
use common\models\EmailLog;
use common\models\Method;
use common\models\Mfa;
use common\models\Password;
use common\models\User;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Sil\EmailService\Client\EmailServiceClient;
use Sil\Psr3Adapters\Psr3Yii2Logger;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\db\Query;

class Emailer extends Component
{
    const SUBJ_INVITE = 'Your new {idpDisplayName} Identity account';
    const SUBJ_MFA_RATE_LIMIT = 'Too many 2-Step Verification attempts on your {idpDisplayName} Identity account';
    const SUBJ_PASSWORD_CHANGED = 'Your {idpDisplayName} Identity account password has been changed';
    const SUBJ_WELCOME = 'Important information about your {idpDisplayName} Identity account';

    const SUBJ_GET_BACKUP_CODES = 'Get printable codes for your {idpDisplayName} account';
    const SUBJ_REFRESH_BACKUP_CODES = 'Get a new set of printable codes for your {idpDisplayName} account';
    const SUBJ_LOST_SECURITY_KEY = 'Have you lost the security key you use with your {idpDisplayName} account?';

    const SUBJ_MFA_OPTION_ADDED = 'A 2-Step Verification option was added to your {idpDisplayName} account';
    const SUBJ_MFA_OPTION_REMOVED = 'A 2-Step Verification option was removed from your {idpDisplayName} account';
    const SUBJ_MFA_ENABLED = '2-Step Verification was enabled on your {idpDisplayName} account';
    const SUBJ_MFA_DISABLED = '2-Step Verification was disabled on your {idpDisplayName} account';
    const SUBJ_MFA_MANAGER = '{displayName} has sent you a login code for their {idpDisplayName} account';
    const SUBJ_MFA_MANAGER_HELP = 'An access code for your {idpDisplayName} account has been sent to your manager';

    const SUBJ_METHOD_VERIFY = 'Please verify your new password recovery method';
    const SUBJ_METHOD_REMINDER = 'REMINDER: Please verify your new password recovery method';
    const SUBJ_METHOD_PURGED = 'An unverified password recovery method has been removed from your {idpDisplayName}'
        . ' account';

    const SUBJ_PASSWORD_EXPIRING = 'The password for your {idpDisplayName} Identity account is about to expire';
    const SUBJ_PASSWORD_EXPIRED = 'The password for your {idpDisplayName} Identity account has expired';

    const PROP_SUBJECT = 'subject';
    const PROP_TO_ADDRESS = 'to_address';
    const PROP_CC_ADDRESS = 'cc_address';
    const PROP_HTML_BODY = 'html_body';
    const PROP_TEXT_BODY = 'text_body';

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

    public $sendMethodReminderEmails = true;
    public $sendMethodPurgedEmails = true;

    public $sendPasswordExpiringEmails = true;
    public $sendPasswordExpiredEmails = true;

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
    public $subjectForMfaManager;
    public $subjectForMfaManagerHelp;

    public $subjectForMethodVerify;
    public $subjectForMethodReminder;
    public $subjectForMethodPurged;

    public $subjectForPasswordExpiring;
    public $subjectForPasswordExpired;

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
            if (! isset($this->emailServiceConfig[$param])) {
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
     * @param string $ccAddress Optional. Email address to include as 'cc'.
     * @throws \Sil\EmailService\Client\EmailServiceClientException
     */
    protected function email(
        string $toAddress,
        string $subject,
        string $htmlBody,
        string $textBody,
        string $ccAddress = ''
    ) {
        $properties = [
            self::PROP_TO_ADDRESS => $toAddress,
            self::PROP_SUBJECT => $subject,
            self::PROP_HTML_BODY => $htmlBody,
            self::PROP_TEXT_BODY => $textBody,
        ];

        if ($ccAddress) {
            $properties[self::PROP_CC_ADDRESS] = $ccAddress;
        }

        $this->getEmailServiceClient()->email($properties);
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
     * Return the subject line for the given $messageType, after substituting
     * $data properties by key into tokens surrounded by {}.
     * @param string $messageType
     * @param array $data properties to insert into subject text
     * @return string
     */
    protected function getSubjectForMessage(string $messageType, array $data): string
    {
        $subject = $this->subjects[$messageType] ?? '';

        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $subject = str_replace('{' . $key . '}', $value, $subject);
            }
        }

        return $subject;
    }

    /**
     * Retrieve the view identifier for the given message type and format. The view is read from a
     * file in common/mail: e.g. mfa-disabled.html.php
     * @param string $messageType Message type -- should be defined in EmailLog getMessageTypes().
     * @param string $format Message format -- must be either 'text' or 'html'
     * @return string
     */
    protected function getViewForMessage(string $messageType, string $format): string
    {
        if (! self::isValidFormat($format)) {
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

        $this->subjectForInvite = $this->subjectForInvite ?? self::SUBJ_INVITE;
        $this->subjectForMfaRateLimit = $this->subjectForMfaRateLimit ?? self::SUBJ_MFA_RATE_LIMIT;
        $this->subjectForPasswordChanged = $this->subjectForPasswordChanged ?? self::SUBJ_PASSWORD_CHANGED;
        $this->subjectForWelcome = $this->subjectForWelcome ?? self::SUBJ_WELCOME;

        $this->subjectForGetBackupCodes = $this->subjectForGetBackupCodes ?? self::SUBJ_GET_BACKUP_CODES;
        $this->subjectForRefreshBackupCodes = $this->subjectForRefreshBackupCodes ?? self::SUBJ_REFRESH_BACKUP_CODES;
        $this->subjectForLostSecurityKey = $this->subjectForLostSecurityKey ?? self::SUBJ_LOST_SECURITY_KEY;

        $this->subjectForMfaOptionAdded = $this->subjectForMfaOptionAdded ?? self::SUBJ_MFA_OPTION_ADDED;
        $this->subjectForMfaOptionRemoved = $this->subjectForMfaOptionRemoved ?? self::SUBJ_MFA_OPTION_REMOVED;
        $this->subjectForMfaEnabled = $this->subjectForMfaEnabled ?? self::SUBJ_MFA_ENABLED;
        $this->subjectForMfaDisabled = $this->subjectForMfaDisabled ?? self::SUBJ_MFA_DISABLED;
        $this->subjectForMfaManager = $this->subjectForMfaManager ?? self::SUBJ_MFA_MANAGER;
        $this->subjectForMfaManagerHelp = $this->subjectForMfaManagerHelp ?? self::SUBJ_MFA_MANAGER_HELP;

        $this->subjectForMethodVerify = $this->subjectForMethodVerify ?? self::SUBJ_METHOD_VERIFY;
        $this->subjectForMethodReminder = $this->subjectForMethodReminder ?? self::SUBJ_METHOD_REMINDER;
        $this->subjectForMethodPurged = $this->subjectForMethodPurged ?? self::SUBJ_METHOD_PURGED;

        $this->subjectForPasswordExpiring = $this->subjectForPasswordExpiring ?? self::SUBJ_PASSWORD_EXPIRING;
        $this->subjectForPasswordExpired = $this->subjectForPasswordExpired ?? self::SUBJ_PASSWORD_EXPIRED;

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
            EmailLog::MESSAGE_TYPE_METHOD_REMINDER => $this->subjectForMethodReminder,
            EmailLog::MESSAGE_TYPE_METHOD_PURGED => $this->subjectForMethodPurged,
            EmailLog::MESSAGE_TYPE_MFA_MANAGER => $this->subjectForMfaManager,
            EmailLog::MESSAGE_TYPE_MFA_MANAGER_HELP => $this->subjectForMfaManagerHelp,
            EmailLog::MESSAGE_TYPE_PASSWORD_EXPIRING => $this->subjectForPasswordExpiring,
            EmailLog::MESSAGE_TYPE_PASSWORD_EXPIRED => $this->subjectForPasswordExpired,
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
        if ($user->active === 'no') {
            \Yii::warning([
                'action' => 'send message',
                'status' => 'canceled',
                'messageType' => $messageType,
                'username' => $user->username,
            ]);
            return;
        }

        $dataForEmail = ArrayHelper::merge(
            $user->getAttributesForEmail(),
            $this->otherDataForEmails,
            $data
        );

        $htmlView = $this->getViewForMessage($messageType, 'html');
        $htmlBody = \Yii::$app->view->render($htmlView, $dataForEmail);

        $textView = $this->getViewForMessage($messageType, 'text');
        $textBody = \Yii::$app->view->render($textView, $dataForEmail);

        $toAddress = $data['toAddress'] ?? $user->getEmailAddress();
        $ccAddress = $data['ccAddress'] ?? '';
        $subject = $this->getSubjectForMessage($messageType, $dataForEmail);

        $this->email($toAddress, $subject, $htmlBody, $textBody, $ccAddress);

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
        if (! $this->sendLostSecurityKeyEmails) {
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
                if (! empty($mfaOption->last_used_utc) && MySqlDateTime::dateIsRecent($mfaOption->last_used_utc, $recentDays)) {
                    return false;
                }

                // If one of the other MFA options has been used recently, remember it.
            } elseif ($lastOtherUseDate === null && ! empty($mfaOption->last_used_utc)) {
                $dateIsRecent = MySqlDateTime::dateIsRecent($mfaOption->last_used_utc, $recentDays);
                $lastOtherUseDate = $dateIsRecent ? $mfaOption->last_used_utc : null;
            }
        }

        // If they don't even have a u2f option, don't send an email
        if (! $hasU2fOption) {
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

    /**
     * Iterates over all unverified methods and sends reminder emails to the user as appropriate
     */
    public function sendMethodReminderEmails()
    {
        if (! $this->sendMethodReminderEmails) {
            return;
        }

        $numEmailsSent = 0;
        $methods = Method::findAll(['verified' => 0]);
        foreach ($methods as $method) {
            $user = $method->user;
            if (! MySqlDateTime::dateIsRecent($method->created, 3) &&
                ! $this->hasReceivedMessageRecently($user->id, EmailLog::MESSAGE_TYPE_METHOD_REMINDER)
            ) {
                $this->sendMessageTo(
                    EmailLog::MESSAGE_TYPE_METHOD_REMINDER,
                    $user,
                    [ 'alternateAddress' => $method->value ]
                );
                $numEmailsSent++;
            }
        }

        $this->logger->info([
            'action' => 'send method reminders',
            'status' => 'finished',
            'number_sent' => $numEmailsSent,
        ]);
    }

    /**
     * Iterates over all active users and sends email alert warning of impending password expiration
     */
    public function sendPasswordExpiringEmails()
    {
        if (! $this->sendPasswordExpiringEmails) {
            return;
        }

        $numEmailsSent = 0;
        $users = User::findAll(['active' => 'yes', 'locked' => 'no', ]);
        foreach ($users as $user) {
            /** @var Password $userPassword */
            $userPassword = $user->currentPassword;
            if ($userPassword
                && strtotime($userPassword->getExpiresOn()) < strtotime('+14 days')
                && ! $this->hasReceivedMessageRecently($user->id, EmailLog::MESSAGE_TYPE_PASSWORD_EXPIRING)
            ) {
                $this->sendMessageTo(EmailLog::MESSAGE_TYPE_PASSWORD_EXPIRING, $user);
                $numEmailsSent++;
            }
        }

        $this->logger->info([
            'action' => 'send password expiring notices',
            'status' => 'finished',
            'number_sent' => $numEmailsSent,
        ]);
    }

    /**
     * Iterates over all active users and sends email alert warning of expired passwords
     */
    public function sendPasswordExpiredEmails()
    {
        if (! $this->sendPasswordExpiredEmails) {
            return;
        }

        $numEmailsSent = 0;
        $users = User::findAll(['active' => 'yes', 'locked' => 'no', ]);
        foreach ($users as $user) {
            /** @var Password $userPassword */
            $userPassword = $user->currentPassword;
            if ($userPassword
                && strtotime($userPassword->getExpiresOn()) < time()
                && ! $this->hasReceivedMessageRecently($user->id, EmailLog::MESSAGE_TYPE_PASSWORD_EXPIRED)
            ) {
                $this->sendMessageTo(EmailLog::MESSAGE_TYPE_PASSWORD_EXPIRED, $user);
                $numEmailsSent++;
            }
        }

        $this->logger->info([
            'action' => 'send password expired notices',
            'status' => 'finished',
            'number_sent' => $numEmailsSent,
        ]);
    }
}
