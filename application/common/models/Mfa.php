<?php

namespace common\models;

use common\components\MfaBackendInterface;
use common\helpers\MySqlDateTime;
use common\helpers\Utils;
use yii\helpers\ArrayHelper;
use yii\validators\EmailValidator;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\TooManyRequestsHttpException;

/**
 * Class Mfa
 * @package common\models
 * @method Mfa self::findOne()
 */
class Mfa extends MfaBase
{
    public const TYPE_TOTP = 'totp';
    public const TYPE_WEBAUTHN = 'webauthn';
    public const TYPE_BACKUPCODE = 'backupcode';
    public const TYPE_MANAGER = 'manager';
    public const TYPE_RECOVERY = 'recovery';

    public const EVENT_TYPE_VERIFY = 'verify_mfa';
    public const EVENT_TYPE_DELETE = 'delete_mfa';

    public const VERIFY_REGISTRATION = 'registration';

    /**
     * Holds additional data about method, such as initialized authentication data
     * needed for WebAuthn methods and number of remaining backup codes
     * @var array
     */
    public array $data = [];

    public function rules(): array
    {
        return ArrayHelper::merge([
            [
                'created_utc', 'default', 'value' => MySqlDateTime::now(),
            ],
            [
                'type', 'in', 'range' => array_keys(self::getTypes()),
            ],
            [
                'verified', 'default', 'value' => 0,
            ],
        ], parent::rules());
    }

    public function fields(): array
    {
        return [
            'id',
            'type',
            'label',
            'created_utc' => function ($model) {
                return Utils::getIso8601($model->created_utc);
            },
            'last_used_utc' => function ($model) {
                if ($model->last_used_utc !== null) {
                    return Utils::getIso8601($model->last_used_utc);
                }
                return null;
            },
            'data',
        ];
    }

    /**
     * @param string $rpOrigin The Relying Party Origin, used for WebAuthn and ignored for others
     */
    public function loadData(string $rpOrigin = '')
    {
        $this->data = [];
        if ($this->verified === 1 && $this->scenario === User::SCENARIO_AUTHENTICATE) {
            try {
                $this->data += $this->authInit($rpOrigin);
            } catch (\Exception $exception) {
                \Yii::error([
                    'action' => 'load ' . $this->type . ' MFA data',
                    'status' => 'error',
                    'error' => 'authInit failed for this MFA option: ' . $exception->getMessage(),
                    'mfa_id' => $this->id,
                ]);
            }
        }
        if ($this->type === self::TYPE_BACKUPCODE || $this->type === self::TYPE_MANAGER) {
            $this->data += ['count' => count($this->mfaBackupcodes)];
        } elseif ($this->type === self::TYPE_WEBAUTHN) {
            $webauthns = $this->mfaWebauthns;
            foreach ($webauthns as $webauthn) {
                $this->data[] = [
                    'id' => $webauthn->id,
                    'label' => $webauthn->label,
                    'last_used_utc' => $webauthn->last_used_utc,
                    'created_utc' => $webauthn->created_utc,
                ];
            }
        }
    }


    /**
     * Whether this is both a new Mfa instance and already verified
     *   (basically just for a new backup code Mfa option)
     *  -- OR --
     * Whether the Mfa option both had its verified value changed and is now verified
     *
     * @param bool $insert Whether the mfa is being inserted
     * @param array $changedAttributes
     * @return bool
     */
    public function isNewlyVerified($insert, $changedAttributes)
    {
        if ($insert  && $this->verified) {
            return true;
        }
        return (array_key_exists('verified', $changedAttributes) && $this->verified);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        /*
         *   Send an "Mfa Added email" for a new backup code option and for
         * other types of mfa options that are newly verified
         *
         *   Don't send emails before they are verified, since the email will
         * not include the most recently added option.
         */
        if ($this->isNewlyVerified($insert, $changedAttributes)) {
            self::sendAppropriateMessages(
                $this->user,
                self::EVENT_TYPE_VERIFY,
                $this
            );

            if (
                !\Yii::$app->params['mfaAllowDisable']
                && $this->user->require_mfa === 'no'
            ) {
                $this->user->require_mfa = 'yes';
                $this->user->scenario = User::SCENARIO_UPDATE_USER;
                $this->user->save();
            }
        }
    }

    /**
     * Before deleting, delete backend record too
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function beforeDelete()
    {
        $this->clearFailedAttempts('when deleting mfa record');

        // first delete the webauthn children to avoid a foreign key constraint error
        foreach ($this->mfaWebauthns as $child) {
            if (!$child->delete()) {
                \Yii::error([
                    'action' => 'delete mfa webauthn child record before deleting mfa',
                    'status' => 'error',
                    'error' => $child->getFirstErrors(),
                    'mfa_id' => $this->id,
                    'child_id' => $child->id,
                ]);
                return false;
            }
        }

        $backend = self::getBackendForType($this->type);
        return $backend->delete($this->id);
    }

    public function afterDelete()
    {
        parent::afterDelete();
        \Yii::warning([
            'action' => 'delete mfa',
            'type' => $this->type,
            'username' => $this->user->username,
            'status' => 'success',
        ]);

        self::sendAppropriateMessages(
            $this->user,
            self::EVENT_TYPE_DELETE,
            $this
        );

        $this->user->extendGracePeriodIfNeeded();
    }

    /**
     * Check if given type is valid
     * @param string $type
     * @return bool
     */
    public static function isValidType(string $type): bool
    {
        return  array_key_exists($type, self::getTypes());
    }

    /**
     * @param string $type
     * @return MfaBackendInterface
     */
    public static function getBackendForType(string $type): MfaBackendInterface
    {
        return \Yii::$app->$type;
    }

    /**
     * @param string $rpOrigin
     * @return array
     */
    public function authInit(string $rpOrigin = ''): array
    {
        $backend = self::getBackendForType($this->type);
        $authInit = $backend->authInit($this->id, $rpOrigin);

        \Yii::warning([
            'action' => 'mfa auth init',
            'type' => $this->type,
            'username' => $this->user->username,
            'status' => 'success',
        ]);

        return $authInit;
    }

    protected function hasTooManyRecentFailures(): bool
    {
        $numRecentFailures = $this->countRecentFailures();
        return ($numRecentFailures >= MfaFailedAttempt::RECENT_FAILURE_LIMIT);
    }

    /**
     * @param string|array $value
     * @param string $rpOrigin Optional
     * @param string $verifyType Optional. If not blank, it must be 'registration', referring to verifying a webauthn registration
     * @return bool
     * @throws ServerErrorHttpException
     * @throws TooManyRequestsHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function verify($value, string $rpOrigin = '', string $verifyType = '', string $label = ''): bool
    {
        if ($this->hasTooManyRecentFailures()) {
            \Yii::warning([
                'action' => 'verify mfa',
                'type' => $this->type,
                'username' => $this->user->username,
                'status' => 'error',
                'error' => 'too many recent failures'
            ]);
            throw new TooManyRequestsHttpException(
                'Too many recent failed attempts for this MFA'
            );
        }

        $backend = self::getBackendForType($this->type);
        if ($backend->verify($this->id, $value, $rpOrigin, $verifyType, $label) === true) {
            $this->last_used_utc = MySqlDateTime::now();
            if (!$this->save()) {
                \Yii::error([
                    'action' => 'update last_used_utc on mfa after verification',
                    'status' => 'error',
                    'username' => $this->user->username,
                    'mfa_id' => $this->id,
                    'error' => $this->getFirstErrors(),
                ]);
            }
            $this->clearFailedAttempts('after successful verification');

            \Yii::warning([
                'action' => 'verify mfa',
                'type' => $this->type,
                'username' => $this->user->username,
                'status' => 'success',
            ]);

            $this->user->removeManagerCodes();
            $this->user->removeRecoveryCodes();

            return true;
        }

        $this->recordFailedAttempt();

        \Yii::warning([
            'action' => 'verify mfa',
            'type' => $this->type,
            'username' => $this->user->username,
            'status' => 'error',
            'error' => 'verify mfa failed'
        ]);

        return false;
    }

    /**
     * @param int $userId
     * @param string $type
     * @param string|null $label
     * @param string $rpOrigin
     * @param string|null $recoveryEmail
     * @return array
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     * @throws ConflictHttpException
     */
    public static function create(int $userId, string $type, ?string $label = null, string $rpOrigin = '', string $recoveryEmail = ''): array
    {
        /*
         * Make sure $type is valid
         */
        if (!self::isValidType($type)) {
            throw new BadRequestHttpException('Invalid MFA type');
        }

        /*
         * Make sure user exists
         */
        $user = User::findOne(['id' => $userId]);
        if ($user == null) {
            throw new BadRequestHttpException("User not found");
        }

        if ($type == self::TYPE_MANAGER && empty($user->manager_email)) {
            throw new BadRequestHttpException('Manager email must be valid for this MFA type');
        }


        if ($type == self::TYPE_RECOVERY) {
            $validator = new EmailValidator();
            if (!$validator->validate($recoveryEmail)) {
                throw new BadRequestHttpException('Recovery email must be valid for this MFA type.', 1742328138);
            }
        }

        $existing = self::findOne(['user_id' => $userId, 'type' => $type, 'verified' => 1]);

        if ($existing instanceof Mfa) {
            if ($type == self::TYPE_TOTP) {
                throw new ConflictHttpException('An MFA of type ' . self::TYPE_TOTP . ' already exists.', 1551190694);
            } else {
                $mfa = $existing;
            }
        } else {
            $mfa = new Mfa();
            $mfa->user_id = $userId;
            $mfa->type = $type;
            $mfa->setLabel($label);

            /*
             * Save $mfa before calling backend->regInit because type backupcode needs mfa record to exist first
             */
            if (!$mfa->save()) {
                \Yii::error([
                    'action' => 'create mfa',
                    'type' => $type,
                    'username' => $user->username,
                    'status' => 'error',
                    'error' => $mfa->getFirstErrors(),
                ]);
                throw new ServerErrorHttpException("Unable to save new MFA record", 1507904193);
            }
        }

        $mfaExtId = $mfa->external_uuid ?: null;
        $backend = self::getBackendForType($type);
        $results = $backend->regInit($userId, $mfaExtId, $rpOrigin, $recoveryEmail);

        if (isset($results['uuid'])) {
            $mfa->external_uuid = $results['uuid'];
            unset($results['uuid']);
            if (!$mfa->save()) {
                \Yii::error([
                    'action' => 'update mfa',
                    'type' => $type,
                    'username' => $user->username,
                    'status' => 'error',
                    'error' => $mfa->getFirstErrors(),
                ]);
                throw new ServerErrorHttpException("Unable to update MFA record", 1507904194);
            }
        }

        \Yii::warning([
            'action' => 'create mfa',
            'type' => $type,
            'username' => $user->username,
            'status' => 'success',
        ]);

        return [
            'id' => $mfa->id,
            'data' => $results,
        ];
    }

    /**
     * Attempt to delete any failed-attempt records for this MFA. If any of
     * the records fail to delete, log an error but keep going (without throwing
     * any exceptions).
     *
     * @param string $context A description (for logging, if this fails) of why
     *     we're trying to clear the failed attempts for this MFA record.
     *     Example: 'while deleting mfa record' or 'after successful verification'.
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function clearFailedAttempts($context)
    {
        foreach ($this->mfaFailedAttempts as $mfaFailedAttempt) {
            if (!$mfaFailedAttempt->delete()) {
                \Yii::error([
                    'action' => 'delete failed attempts ' . $context,
                    'status' => 'error',
                    'error' => $mfaFailedAttempt->getFirstErrors(),
                    'mfa_id' => $this->id,
                    'mfa_failed_attempt_id' => $mfaFailedAttempt->id,
                ]);
                // NOTE: Continue even if this deletion fails.
            }
        }
    }

    /**
     * Get the number of "recent" failed attempts to verify a value for this
     * MFA record.
     *
     * @return int|string The number of failed attempts.
     *
     *     NOTE: Yii sometimes returns integers from the database as strings.
     */
    public function countRecentFailures()
    {
        $cutoffForRecent = MySqlDateTime::relativeTime('-5 minutes');

        return $this->getMfaFailedAttempts()->where(
            ['>', 'at_utc', $cutoffForRecent]
        )->count();
    }

    /**
     * Record a failed verification attempt for this MFA. If unable to do so for
     * some reason, fail loudly (because we need to know about and fix this).
     *
     * @throws ServerErrorHttpException
     */
    public function recordFailedAttempt()
    {
        $mfaFailedAttempt = new MfaFailedAttempt([
            'mfa_id' => $this->id,
        ]);
        if (!$mfaFailedAttempt->save()) {
            \Yii::error([
                'action' => 'record mfa failed attempt',
                'status' => 'error',
                'error' => $mfaFailedAttempt->getFirstErrors(),
                'mfa_id' => $this->id,
            ]);
            throw new ServerErrorHttpException(
                'Failed to record failed attempt for this MFA',
                1510083458
            );
        }

        if ($this->hasTooManyRecentFailures()) {
            \Yii::warning([
                'action' => 'MFA rate limit triggered',
                'mfa_id' => $this->id,
                'mfa_type' => $this->type,
                'status' => 'warning',
                'username' => $this->user->username,
            ]);

            /* @var \common\components\Emailer $emailer */
            $emailer = \Yii::$app->emailer;
            $emailer->sendMessageTo(
                EmailLog::MESSAGE_TYPE_MFA_RATE_LIMIT,
                $this->user
            );
        }
    }

    /**
     * Returns a list of MFA types and user-friendly name
     *
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::TYPE_BACKUPCODE => 'Printable Codes',
            self::TYPE_MANAGER => 'Manager Backup Code',
            self::TYPE_TOTP => 'Authenticator App',
            self::TYPE_WEBAUTHN => 'Security Key',
            self::TYPE_RECOVERY => 'Recovery Contact Code',
        ];
    }

    /**
     * Returns a human friendly version of the Mfa's type
     *
     * @return string
     */
    public function getReadableType()
    {
        $types = self::getTypes();
        return $types[$this->type];
    }

    /**
     * Remove records that were not verified within the given time frame
     * @throws \Throwable
     */
    public static function removeOldUnverifiedRecords()
    {
        self::deleteOldRecords(\Yii::$app->params['mfaLifetime'], ['verified' => 0]);
    }

    /**
     * @param string $age PHP relative time denoting how old a record must be to qualify for removal
     * @param array $criteria array suitable for passing to a query, like ['field' => value]
     */
    protected static function deleteOldRecords(string $age, array $criteria): void
    {
        if (!preg_match('/[\+\-].*/', $age)) {
            $age = '-' . $age;
        }

        $age = str_replace('+', '-', $age);

        /**
         * @var string $removeOlderThan  Records created before this date should be deleted
         * (if they also satisfy $criteria). Calculated relative to now (time of execution).
         */
        $removeOlderThan = MySqlDateTime::relativeTime($age);
        /** @var Mfa[] $mfas */
        $mfas = self::find()
            ->where($criteria)
            ->andWhere(['<', 'created_utc', $removeOlderThan])
            ->all();

        \Yii::warning([
            'action' => 'delete old mfa records',
            'criteria' => $criteria,
            'status' => 'starting',
            'age' => $age,
            'removeOlderThan' => $removeOlderThan,
            'count' => count($mfas),
        ]);

        $numDeleted = 0;
        foreach ($mfas as $mfa) {
            try {
                if ($mfa->delete() === false) {
                    \Yii::error([
                        'action' => 'delete old mfa records',
                        'criteria' => $criteria,
                        'status' => 'error',
                        'error' => $mfa->getFirstErrors(),
                        'mfa_id' => $mfa->id,
                    ]);
                } else {
                    $numDeleted += 1;
                }
            } catch (\Exception $e) {
                \Yii::error([
                    'action' => 'delete old mfa records',
                    'criteria' => $criteria,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'mfa_id' => $mfa->id,
                ]);
            }
        }

        \Yii::warning([
            'action' => 'delete old mfa records',
            'criteria' => $criteria,
            'status' => 'complete',
            'count' => $numDeleted,
        ]);
    }

    /**
     * @param User $user
     * @param string $eventType
     * @param Mfa $mfa
     */
    protected static function sendAppropriateMessages($user, $eventType, $mfa)
    {
        if ($mfa->type === self::TYPE_MANAGER || $mfa->type === self::TYPE_RECOVERY) {
            return;
        }

        /* @var \common\components\Emailer $emailer */
        $emailer = \Yii::$app->emailer;
        $user->refresh();

        if ($emailer->shouldSendMfaOptionAddedMessageTo($user, $eventType)) {
            $emailer->sendMessageTo(
                EmailLog::MESSAGE_TYPE_MFA_OPTION_ADDED,
                $user
            );
        } elseif ($emailer->shouldSendMfaEnabledMessageTo($user, $eventType)) {
            $emailer->sendMessageTo(
                EmailLog::MESSAGE_TYPE_MFA_ENABLED,
                $user
            );
        } elseif ($emailer->shouldSendMfaOptionRemovedMessageTo($user, $eventType, $mfa)) {
            $emailer->otherDataForEmails['mfaTypeDisabled'] = $mfa->getReadableType();
            $emailer->sendMessageTo(
                EmailLog::MESSAGE_TYPE_MFA_OPTION_REMOVED,
                $user
            );
        } elseif ($emailer->shouldSendMfaDisabledMessageTo($user, $eventType, $mfa)) {
            $emailer->sendMessageTo(
                EmailLog::MESSAGE_TYPE_MFA_DISABLED,
                $user
            );
        }
    }

    /**
     * Set verified=1 and save if necessary
     * @throws \Exception if save failed
     */
    public function setVerified(): void
    {
        if ($this->verified == 0) {
            $this->verified = 1;
            if (!$this->save()) {
                throw new \Exception("Error saving MFA record", 1547066350);
            }
        }
    }

    /**
     * Set the label. If `$label` is null, use a default label for the mfa type. If `$label`
     * is a string, simply assign it to the `label` property.
     * @param mixed $label
     */
    public function setLabel($label)
    {
        if (is_string($label) && !empty($label)) {
            $this->label = $label;
        } else {
            $this->label = $this->getReadableType();
        }
    }

    public static function removeOldManagerMfaRecords(): void
    {
        self::deleteOldRecords('1 week', ['type' => Mfa::TYPE_MANAGER]);
    }

    public static function removeOldRecoveryMfaRecords(): void
    {
        self::deleteOldRecords('1 week', ['type' => Mfa::TYPE_RECOVERY]);
    }
}
