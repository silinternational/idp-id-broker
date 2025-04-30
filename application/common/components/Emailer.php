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
use Sil\Psr3Adapters\Psr3Yii2Logger;
use yii\base\Component;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

class Emailer extends Component
{
    public const SUBJ_INVITE = 'Your new {idpDisplayName} Identity account';
    public const SUBJ_MFA_RATE_LIMIT = 'Too many 2-Step Verification attempts on your {idpDisplayName} Identity account';
    public const SUBJ_PASSWORD_CHANGED = 'Your {idpDisplayName} Identity account password has been changed';
    public const SUBJ_WELCOME = 'Important information about your {idpDisplayName} Identity account';

    public const SUBJ_GET_BACKUP_CODES = 'Get printable codes for your {idpDisplayName} Identity account';
    public const SUBJ_REFRESH_BACKUP_CODES = 'Get a new set of printable codes for your {idpDisplayName} Identity account';
    public const SUBJ_LOST_SECURITY_KEY = 'Do you still have the security key you use with your {idpDisplayName}'
        . ' Identity account?';

    public const SUBJ_MFA_OPTION_ADDED = 'A 2-Step Verification option was added to your {idpDisplayName} Identity account';
    public const SUBJ_MFA_OPTION_REMOVED = 'A 2-Step Verification option was removed from your {idpDisplayName}'
        . ' Identity account';
    public const SUBJ_MFA_ENABLED = '2-Step Verification was enabled on your {idpDisplayName} Identity account';
    public const SUBJ_MFA_DISABLED = '2-Step Verification was disabled on your {idpDisplayName} Identity account';
    public const SUBJ_MFA_RECOVERY = '{displayName} has sent you a login code for their {idpDisplayName} Identity account';
    public const SUBJ_MFA_RECOVERY_HELP = 'An access code for your {idpDisplayName} Identity account has been sent to'
        . ' your recovery contact';
    public const SUBJ_METHOD_VERIFY = 'Please verify your new password recovery method';
    public const SUBJ_METHOD_REMINDER = 'REMINDER: Please verify your new password recovery method';
    public const SUBJ_METHOD_PURGED = 'An unverified password recovery method has been removed from your {idpDisplayName}'
        . ' Identity account';

    public const SUBJ_PASSWORD_EXPIRING = 'The password for your {idpDisplayName} Identity account is about to expire';
    public const SUBJ_PASSWORD_EXPIRED = 'The password for your {idpDisplayName} Identity account has expired';
    public const SUBJ_PASSWORD_PWNED = 'ALERT: The password for your {idpDisplayName} Identity account has been exposed';

    public const SUBJ_ABANDONED_USER_ACCOUNTS = 'Unused {idpDisplayName} Identity Accounts';

    public const SUBJ_EXT_GROUP_SYNC_ERRORS = "Errors while syncing '{appPrefix}' external-groups to the {idpDisplayName} IDP";

    public const PROP_SUBJECT = 'subject';
    public const PROP_TO_ADDRESS = 'to_address';
    public const PROP_CC_ADDRESS = 'cc_address';
    public const PROP_BCC_ADDRESS = 'bcc_address';
    public const PROP_HTML_BODY = 'html_body';
    public const PROP_TEXT_BODY = 'text_body';
    public const PROP_DELAY_SECONDS = 'delay_seconds';

    public const PASSWORD_EXPIRED_CUTOFF = '-15 days';
    public const PASSWORD_EXPIRING_CUTOFF = '+15 days';

    /** @var EmailClient */
    protected $emailClient = null;

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
    public $subjectForMfaRecovery;
    public $subjectForMfaRecoveryHelp;
    public $subjectForMethodVerify;
    public $subjectForMethodReminder;
    public $subjectForMethodPurged;

    public $subjectForPasswordExpiring;
    public $subjectForPasswordExpired;
    public $subjectForPasswordPwned;

    public $subjectForAbandonedUsers;

    public $subjectForExtGroupSyncErrors;

    /* The email to contact for HR notifications */
    public $hrNotificationsEmail;

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
     * Use the email client to send an email.
     *
     * WARNING:
     * You probably shouldn't be calling this directly. Instead, call the
     * `sendMessageTo()` method so that the sending of this email will be
     * logged.
     *
     * @param string $toAddress The recipient's email address.
     * @param string $subject The subject.
     * @param string $htmlBody The email body (as HTML).
     * @param string $textBody The email body (as plain text).
     * @param string $ccAddress Optional. Email address to include as 'cc'.
     * @param string $bccAddress Optional. Email address to include as 'bcc'.
     * @param int $delaySeconds Number of seconds to delay sending the email. Default = no delay.
     */
    protected function email(
        string $toAddress,
        string $subject,
        string $htmlBody,
        string $textBody,
        string $ccAddress = '',
        string $bccAddress = '',
        int $delaySeconds = 0
    ) {
        $properties = [
            self::PROP_TO_ADDRESS => $toAddress,
            self::PROP_SUBJECT => $subject,
            self::PROP_HTML_BODY => $htmlBody,
            self::PROP_TEXT_BODY => $textBody,
            self::PROP_DELAY_SECONDS => $delaySeconds,
        ];

        if ($ccAddress) {
            $properties[self::PROP_CC_ADDRESS] = $ccAddress;
        }

        if ($bccAddress) {
            $properties[self::PROP_BCC_ADDRESS] = $bccAddress;
        }

        $this->getEmailClient()->email($properties);
    }

    /**
     * @return EmailClient
     */
    protected function getEmailClient()
    {
        if ($this->emailClient === null) {
            $this->emailClient = new EmailClient();
        }

        return $this->emailClient;
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

        if (empty($subject)) {
            \Yii::error(sprintf(
                'No subject known for %s email messages.',
                $messageType
            ));
        }

        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $subject = str_replace('{' . $key . '}', $value, $subject);
            }
        }

        return $subject;
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
        $this->subjectForMfaRecovery = $this->subjectForMfaRecovery ?? self::SUBJ_MFA_RECOVERY;
        $this->subjectForMfaRecoveryHelp = $this->subjectForMfaRecoveryHelp ?? self::SUBJ_MFA_RECOVERY_HELP;

        $this->subjectForMethodVerify = $this->subjectForMethodVerify ?? self::SUBJ_METHOD_VERIFY;
        $this->subjectForMethodReminder = $this->subjectForMethodReminder ?? self::SUBJ_METHOD_REMINDER;
        $this->subjectForMethodPurged = $this->subjectForMethodPurged ?? self::SUBJ_METHOD_PURGED;

        $this->subjectForPasswordExpiring = $this->subjectForPasswordExpiring ?? self::SUBJ_PASSWORD_EXPIRING;
        $this->subjectForPasswordExpired = $this->subjectForPasswordExpired ?? self::SUBJ_PASSWORD_EXPIRED;
        $this->subjectForPasswordPwned = $this->subjectForPasswordPwned ?? self::SUBJ_PASSWORD_PWNED;

        $this->subjectForAbandonedUsers = $this->subjectForAbandonedUsers ?? self::SUBJ_ABANDONED_USER_ACCOUNTS;

        $this->subjectForExtGroupSyncErrors = $this->subjectForExtGroupSyncErrors ?? self::SUBJ_EXT_GROUP_SYNC_ERRORS;

        $this->subjects = [
            EmailLog::MESSAGE_TYPE_INVITE => $this->subjectForInvite,
            EmailLog::MESSAGE_TYPE_MFA_RATE_LIMIT => $this->subjectForMfaRateLimit,
            EmailLog::MESSAGE_TYPE_PASSWORD_CHANGED => $this->subjectForPasswordChanged,
            EmailLog::MESSAGE_TYPE_WELCOME => $this->subjectForWelcome,
            EmailLog::MESSAGE_TYPE_ABANDONED_USERS => $this->subjectForAbandonedUsers,
            EmailLog::MESSAGE_TYPE_EXT_GROUP_SYNC_ERRORS => $this->subjectForExtGroupSyncErrors,
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
            EmailLog::MESSAGE_TYPE_MFA_RECOVERY => $this->subjectForMfaRecovery,
            EmailLog::MESSAGE_TYPE_MFA_RECOVERY_HELP => $this->subjectForMfaRecoveryHelp,
            EmailLog::MESSAGE_TYPE_PASSWORD_EXPIRING => $this->subjectForPasswordExpiring,
            EmailLog::MESSAGE_TYPE_PASSWORD_EXPIRED => $this->subjectForPasswordExpired,
            EmailLog::MESSAGE_TYPE_PASSWORD_PWNED => $this->subjectForPasswordPwned,
        ];

        $this->hrNotificationsEmail = $this->hrNotificationsEmail ?? '';

        $this->assertConfigIsValid();

        $this->verifyOtherDataForEmailIsValid();

        parent::init();
    }

    /**
     * Send the specified type of message to the given User (or non-User address).
     *
     * @param string $messageType The message type. Must be one of the
     *     EmailLog::MESSAGE_TYPE_* values.
     * @param ?User $user The intended recipient, if applicable. If not provided, a 'toAddress'
     *     must be in the $data parameter.
     * @param string[] $data Data fields for email template. Include key 'toAddress' to override
     *     sending to primary address in User object.
     * @param int $delaySeconds Number of seconds to delay sending the email. Default = no delay.
     */
    public function sendMessageTo(
        string $messageType,
        ?User $user = null,
        array $data = [],
        int $delaySeconds = 0
    ) {
        if ($user && $user->active === 'no') {
            \Yii::warning([
                'action' => 'send message',
                'status' => 'canceled',
                'messageType' => $messageType,
                'username' => $user->username,
            ]);
            return;
        }

        $dataForEmail = ArrayHelper::merge(
            $user ? $user->getAttributesForEmail() : [],
            $this->otherDataForEmails,
            $data
        );

        $htmlView = sprintf('@common/mail/%s.html.php', Inflector::slug($messageType));
        $htmlBody = \Yii::$app->view->render($htmlView, $dataForEmail);

        $toAddress = $data['toAddress'] ?? $user->getEmailAddress();
        $ccAddress = $data['ccAddress'] ?? '';
        $bccAddress = $data['bccAddress'] ?? '';
        $subject = $this->getSubjectForMessage($messageType, $dataForEmail);

        $this->email($toAddress, $subject, $htmlBody, strip_tags($htmlBody), $ccAddress, $bccAddress, $delaySeconds);

        if ($user !== null) {
            EmailLog::logMessageToUser($messageType, $user->id);
        } else {
            EmailLog::logMessageToNonUser($messageType, $toAddress);
        }
    }

    /**
     * Iterates over all users and sends get-backup-code and/or lost-security-key emails as is appropriate
     */
    public function sendDelayedMfaRelatedEmails()
    {
        $query = (new Query())->from('user')->where(['active' => 'yes']);

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
     * Whether the user has already been sent this type of email in the last X days
     *
     * @param int $userId
     * @param string $messageType
     * @return bool
     */
    public function hasUserReceivedMessageRecently(int $userId, string $messageType): bool
    {
        $latestEmail = EmailLog::find()->where(['user_id' => $userId, 'message_type' => $messageType])
            ->orderBy('sent_utc DESC')->one();
        if (empty($latestEmail)) {
            return false;
        }

        return MySqlDateTime::dateIsRecent($latestEmail->sent_utc, $this->emailRepeatDelayDays);
    }

    /**
     * Whether the non-user address has already been sent this type of email
     * recently (where "recent" is defined by the given timeframe).
     *
     * @param string $emailAddress
     * @param string $messageType
     * @param ?string $timeframe (Optional:) What qualifies as recent. If not
     *     specified, this will default to `emailRepeatDelayDays` days.
     *     Example: '11 hours'
     * @return bool
     */
    public function hasNonUserReceivedMessageRecently(
        string $emailAddress,
        string $messageType,
        ?string $timeframe = null
    ): bool {
        $latestEmail = EmailLog::find()->where([
            'message_type' => $messageType,
            'non_user_address' => $emailAddress,
            'user_id' => null,
        ])->orderBy(
            'sent_utc DESC'
        )->one();

        if (empty($latestEmail)) {
            return false;
        }

        if (empty($timeframe)) {
            $timeframe = $this->emailRepeatDelayDays . ' days';
        }

        return MySqlDateTime::dateTimeIsRecent($latestEmail->sent_utc, $timeframe);
    }

    /**
     * Whether we should send an abandoned-users message to HR.
     *
     * @return bool
     */
    public function shouldSendAbandonedUsersMessage(): bool
    {
        if (empty($this->hrNotificationsEmail)) {
            return false;
        }

        $haveSentAbandonedUsersEmailRecently = $this->hasNonUserReceivedMessageRecently(
            $this->hrNotificationsEmail,
            EmailLog::MESSAGE_TYPE_ABANDONED_USERS
        );

        return !$haveSentAbandonedUsersEmailRecently;
    }

    /**
     * Whether we should send an external-groups sync-errors email to the given
     * email address.
     *
     * @param string $emailAddress
     * @return bool
     */
    public function shouldSendExternalGroupsSyncErrorsEmailTo(string $emailAddress): bool
    {
        if (empty($emailAddress)) {
            return false;
        }

        $haveSentEmailRecently = $this->hasNonUserReceivedMessageRecently(
            $emailAddress,
            EmailLog::MESSAGE_TYPE_EXT_GROUP_SYNC_ERRORS,
            '11 hours' /* Use slightly less than the desired interval, to avoid
                        * inconsistent results due to being a few seconds before
                        * or after the cutoff. */
        );

        return !$haveSentEmailRecently;
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
            && $user->getVerifiedMfaOptionsCount() === 1
            && !$user->hasMfaBackupCodes()
            && !$this->hasUserReceivedMessageRecently($user->id, EmailLog::MESSAGE_TYPE_GET_BACKUP_CODES);
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
     * If a TOTP or backup code was used in the last X days but not a WebAuthn option
     *   and if we are configured to send this sort of email, then
     *   send it.
     *
     * @param User $user
     * @return bool
     */
    public function shouldSendLostSecurityKeyMessageTo($user)
    {
        if (!$this->sendLostSecurityKeyEmails) {
            return false;
        }

        if ($this->hasUserReceivedMessageRecently($user->id, EmailLog::MESSAGE_TYPE_LOST_SECURITY_KEY)) {
            return false;
        }

        $hasWebAuthnOption = false;
        $lastOtherUseDate = null;
        $mfaOptions = $user->getVerifiedMfaOptions();

        $recentDays = $this->lostSecurityKeyEmailDays;

        // Get the dates of the last use of the MFA options
        foreach ($mfaOptions as $mfaOption) {

            // If this is a Security Key and it was used recently, don't send an email.
            if ($mfaOption->type === Mfa::TYPE_WEBAUTHN) {
                $hasWebAuthnOption = true;
                if (!empty($mfaOption->last_used_utc) && MySqlDateTime::dateIsRecent($mfaOption->last_used_utc, $recentDays)) {
                    return false;
                }

                // If one of the other MFA options has been used recently, remember it.
            } elseif ($lastOtherUseDate === null && !empty($mfaOption->last_used_utc)) {
                $dateIsRecent = MySqlDateTime::dateIsRecent($mfaOption->last_used_utc, $recentDays);
                $lastOtherUseDate = $dateIsRecent ? $mfaOption->last_used_utc : null;
            }
        }

        // If they don't even have a webauthn option, don't send an email
        if (!$hasWebAuthnOption) {
            return false;
        }

        // If a totp or backup code was used in the last X days (but not the webauthn option), send an email
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
            && $user->getVerifiedMfaOptionsCount() > 1;
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
            && $user->getVerifiedMfaOptionsCount() == 1;
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
            && $user->getVerifiedMfaOptionsCount() > 0;
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
            && $user->getVerifiedMfaOptionsCount() < 1;
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
                if ($keyForEmail === 'helpCenterUrl') {
                    /*
                     * helpCenterUrl is not required
                     */
                    continue;
                }

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
        if (!$this->sendMethodReminderEmails) {
            return;
        }

        $numEmailsSent = 0;
        $methods = Method::findAll(['verified' => 0]);
        foreach ($methods as $method) {
            $user = $method->user;
            if (!MySqlDateTime::dateIsRecent($method->created, 3) &&
                !$this->hasUserReceivedMessageRecently($user->id, EmailLog::MESSAGE_TYPE_METHOD_REMINDER)
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
        if (!$this->sendPasswordExpiringEmails) {
            return;
        }

        $logData = [
            'action' => 'send password expiring notices',
            'status' => 'starting',
        ];

        $users = User::getUsersForEmail('password-expiring', $this->emailRepeatDelayDays);

        $this->logger->info(array_merge($logData, [
            'users' => count($users)
        ]));

        $numEmailsSent = 0;
        foreach ($users as $user) {
            /** @var Password $userPassword */
            $userPassword = $user->currentPassword;
            if ($userPassword) {
                $passwordExpiry = strtotime($userPassword->getExpiresOn());
                if ($passwordExpiry < strtotime(self::PASSWORD_EXPIRING_CUTOFF)
                    && !($passwordExpiry < time())
                ) {
                    $this->sendMessageTo(EmailLog::MESSAGE_TYPE_PASSWORD_EXPIRING, $user);
                    $numEmailsSent++;
                }
            }
        }

        $this->logger->info(array_merge($logData, [
            'status' => 'finished',
            'number_sent' => $numEmailsSent,
        ]));
    }

    /**
     * Iterates over all active users and sends email alert warning of expired passwords
     */
    public function sendPasswordExpiredEmails()
    {
        if (!$this->sendPasswordExpiredEmails) {
            return;
        }

        $logData = [
            'action' => 'send password expired notices',
            'status' => 'starting',
        ];

        $users = User::getUsersForEmail('password-expired', $this->emailRepeatDelayDays);

        $this->logger->info(array_merge($logData, [
            'users' => count($users)
        ]));

        $numEmailsSent = 0;
        foreach ($users as $user) {
            /** @var Password $userPassword */
            $userPassword = $user->currentPassword;
            if ($userPassword) {
                $passwordExpiry = strtotime($userPassword->getExpiresOn());
                if ($passwordExpiry < time()
                    && $passwordExpiry > strtotime(self::PASSWORD_EXPIRED_CUTOFF)
                ) {
                    $this->sendMessageTo(EmailLog::MESSAGE_TYPE_PASSWORD_EXPIRED, $user);
                    $numEmailsSent++;
                }
            }
        }

        $this->logger->info(array_merge($logData, [
            'status' => 'finished',
            'number_sent' => $numEmailsSent,
        ]));
    }

    public function sendExternalGroupSyncErrorsEmail(
        string $appPrefix,
        array $errors,
        string $recipient,
        string $googleSheetUrl
    ) {
        $logData = [
            'action' => 'send external-groups sync errors email',
            'prefix' => $appPrefix,
        ];

        if (!$this->shouldSendExternalGroupsSyncErrorsEmailTo($recipient)) {
            $this->logger->info(array_merge($logData, [
                'errors' => count($errors),
                'recipient' => $recipient,
                'status' => 'skipping (too soon to resend)',
            ]));
        } else {
            $this->logger->info(array_merge($logData, [
                'errors' => count($errors),
                'recipient' => $recipient,
                'status' => 'starting',
            ]));

            $this->sendMessageTo(
                EmailLog::MESSAGE_TYPE_EXT_GROUP_SYNC_ERRORS,
                null,
                [
                    'toAddress' => $recipient,
                    'appPrefix' => $appPrefix,
                    'errors' => $errors,
                    'googleSheetUrl' => $googleSheetUrl,
                    'idpDisplayName' => \Yii::$app->params['idpDisplayName'],
                ]
            );

            $this->logger->info(array_merge($logData, [
                'status' => 'finished',
            ]));
        }
    }

    /**
     * Sends email alert to HR with all abandoned users, if any
     */
    public function sendAbandonedUsersEmail()
    {
        $dataForEmail = \Yii::$app->params['abandonedUser'];
        $dataForEmail = ArrayHelper::merge(
            $this->otherDataForEmails,
            $dataForEmail
        );

        if (!empty($this->hrNotificationsEmail)) {
            $dataForEmail['users'] = User::getAbandonedUsers();

            if (!empty($dataForEmail['users'])) {
                if ($this->shouldSendAbandonedUsersMessage()) {
                    $this->sendMessageTo(
                        EmailLog::MESSAGE_TYPE_ABANDONED_USERS,
                        null,
                        ArrayHelper::merge(
                            $dataForEmail,
                            ['toAddress' => $this->hrNotificationsEmail]
                        )
                    );
                }
            }
        }
    }
}
