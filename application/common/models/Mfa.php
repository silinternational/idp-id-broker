<?php
namespace common\models;

use common\components\MfaBackendInterface;
use common\helpers\MySqlDateTime;
use common\helpers\Utils;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\TooManyRequestsHttpException;

/**
 * Class Mfa
 * @package common\models
 * @method Mfa self::findOne()
 */
class Mfa extends MfaBase
{
    const TYPE_TOTP = 'totp';
    const TYPE_U2F = 'u2f';
    const TYPE_BACKUPCODE = 'backupcode';
    const TYPE_MANAGER = 'manager';

    const EVENT_TYPE_VERIFY = 'verify_mfa';
    const EVENT_TYPE_DELETE = 'delete_mfa';

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
            'created_utc' => function($model) {
                return Utils::getIso8601($model->created_utc);
            },
            'last_used_utc' => function($model) {
                if ($model->last_used_utc !== null) {
                    return Utils::getIso8601($model->last_used_utc);
                }
                return null;
            },
            'data' => function($model) {
                $data = [];
                /** @var Mfa $model */
                if ($model->verified === 1 && $model->scenario === User::SCENARIO_AUTHENTICATE) {
                    $data += $model->authInit();
                }
                if ($model->type === self::TYPE_BACKUPCODE || $model->type === self::TYPE_MANAGER) {
                    $data += ['count' => count($model->mfaBackupcodes)];
                }
                return $data;
            }
        ];
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
        }
    }

    /**
     * Before deleting, delete backend record too
     * @return bool
     */
    public function beforeDelete()
    {
        $this->clearFailedAttempts('when deleting mfa record');
        
        $backend = self::getBackendForType($this->type);
        return $backend->delete($this->id);
    }

    public function afterDelete()
    {
        parent::afterDelete();
        \Yii::warning([
            'action' => 'delete mfa',
            'type' => $this->type,
            'user' => $this->user->email,
            'status' => 'success',
        ]);

        self::sendAppropriateMessages(
            $this->user,
            self::EVENT_TYPE_DELETE,
            $this
        );
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
     * @return array
     */
    public function authInit()
    {
        $backend = self::getBackendForType($this->type);
        $authInit = $backend->authInit($this->id);

        \Yii::warning([
            'action' => 'mfa auth init',
            'type' => $this->type,
            'user' => $this->user->email,
            'status' => 'success',
        ]);

        return $authInit;
    }
    
    protected function hasTooManyRecentFailures()
    {
        $numRecentFailures = $this->countRecentFailures();
        return ($numRecentFailures >= MfaFailedAttempt::RECENT_FAILURE_LIMIT);
    }

    /**
     * @param string|array $value
     * @return bool
     */
    public function verify($value): bool
    {
        if ($this->hasTooManyRecentFailures()) {
            \Yii::warning([
                'action' => 'verify mfa',
                'type' => $this->type,
                'user' => $this->user->email,
                'status' => 'error',
                'error' => 'too many recent failures'
            ]);
            throw new TooManyRequestsHttpException(
                'Too many recent failed attempts for this MFA'
            );
        }
        
        $backend = self::getBackendForType($this->type);
        if ($backend->verify($this->id, $value) === true) {
            $this->last_used_utc = MySqlDateTime::now();
            if ( ! $this->save()) {
                \Yii::error([
                    'action' => 'update last_used_utc on mfa after verification',
                    'status' => 'error',
                    'user' => $this->user->email,
                    'mfa_id' => $this->id,
                    'error' => $this->getFirstErrors(),
                ]);
            }
            $this->clearFailedAttempts('after successful verification');

            \Yii::warning([
                'action' => 'verify mfa',
                'type' => $this->type,
                'user' => $this->user->email,
                'status' => 'success',
            ]);
            return true;
        }

        $this->recordFailedAttempt();

        \Yii::warning([
            'action' => 'verify mfa',
            'type' => $this->type,
            'user' => $this->user->email,
            'status' => 'error',
            'error' => 'verify mfa failed'
        ]);

        return false;
    }

    /**
     * @param int $userId
     * @param string $type
     * @return array
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    public static function create(int $userId, string $type, string $label = null): array
    {
        /*
         * Make sure $type is valid
         */
        if ( ! self::isValidType($type)) {
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

        $mfa = new Mfa();

        /*
         * User can only have one 'backupcode' or 'manager' type, so if already exists, use existing
         */
        if ($type == self::TYPE_BACKUPCODE || $type == self::TYPE_MANAGER) {
            $existing = self::findOne(['user_id' => $userId, 'type' => $type]);
            if ($existing instanceof Mfa) {
                $mfa = $existing;
            }
        }

        $mfa->user_id = $userId;
        $mfa->type = $type;

        if (empty($label)) {
            $existingCount = count(array_filter($user->mfas, function ($otherMfa) use ($mfa) {
                return $otherMfa->type === $mfa->type;
            }));
            $mfa->setLabel($existingCount + 1);
        } else {
            $mfa->setLabel($label);
        }

        /*
         * Save $mfa before calling backend->regInit because type backupcode needs mfa record to exist first
         */
        if ( ! $mfa->save()) {
            \Yii::error([
                'action' => 'create mfa',
                'type' => $type,
                'user' => $user->email,
                'status' => 'error',
                'error' => $mfa->getFirstErrors(),
            ]);
            throw new ServerErrorHttpException("Unable to save new MFA record", 1507904193);
        }

        $backend = self::getBackendForType($type);
        $results = $backend->regInit($userId);

        if (isset($results['uuid'])) {
            $mfa->external_uuid = $results['uuid'];
            unset($results['uuid']);
            if ( ! $mfa->save()) {
                \Yii::error([
                    'action' => 'update mfa',
                    'type' => $type,
                    'user' => $user->email,
                    'status' => 'error',
                    'error' => $mfa->getFirstErrors(),
                ]);
                throw new ServerErrorHttpException("Unable to update MFA record", 1507904194);
            }
        }

        \Yii::warning([
            'action' => 'create mfa',
            'type' => $type,
            'user' => $user->email,
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
                'user' => $this->user->email,
            ]);
            
            /* @var $emailer Emailer */
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
            self::TYPE_TOTP => 'Smartphone App',
            self::TYPE_U2F => 'Security Key',
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
     * @param int $maxAgeHours
     */
    public static function removeOldUnverifiedRecords()
    {
        /*
         * Replace '+' with '-' so all env parameters can be defined consistently as '+n unit'
         */
        $mfaLifetime = str_replace('+', '-', \Yii::$app->params['mfaLifetime']);

        /**
         * @var string $removeExpireBefore   All unverified records that expired before this date
         * should be deleted. Calculated relative to now (time of execution).
         */
        $removeOlderThan = MySqlDateTime::relativeTime($mfaLifetime);
        $mfas = self::find()
            ->where(['verified' => 0])
            ->andWhere(['<', 'created_utc', $removeOlderThan])
            ->all();

        $numDeleted = 0;
        foreach ($mfas as $mfa) {
            if ($mfa->delete() === false) {
                \Yii::error([
                    'action' => 'delete old unverified mfa records',
                    'status' => 'error',
                    'error' => $mfa->getFirstErrors(),
                    'mfa_id' => $mfa->id,
                ]);
            } else {
                $numDeleted += 1;
            }
        }
        
        \Yii::warning([
            'action' => 'delete old unverified mfa records',
            'status' => 'complete',
            'count' => $numDeleted,
        ]);
    }


    protected static function sendAppropriateMessages($user, $eventType, $mfa)
    {
        /* @var $emailer Emailer */
        $emailer = \Yii::$app->emailer;
        $user->refresh();

        if ($emailer->shouldSendMfaOptionAddedMessageTo($user, $eventType)) {
            $emailer->sendMessageTo(EmailLog::MESSAGE_TYPE_MFA_OPTION_ADDED, $user);

        } else if ($emailer->shouldSendMfaEnabledMessageTo($user, $eventType)) {
            $emailer->sendMessageTo(EmailLog::MESSAGE_TYPE_MFA_ENABLED, $user);

        } else if ($emailer->shouldSendMfaOptionRemovedMessageTo($user, $eventType, $mfa)) {
            $emailer->otherDataForEmails['mfaTypeDisabled'] = $mfa->getReadableType();
            $emailer->sendMessageTo(EmailLog::MESSAGE_TYPE_MFA_OPTION_REMOVED, $user);

        } else if ($emailer->shouldSendMfaDisabledMessageTo($user, $eventType, $mfa)) {
            $emailer->sendMessageTo(EmailLog::MESSAGE_TYPE_MFA_DISABLED, $user);
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
            if (! $this->save()) {
                throw new \Exception("Error saving MFA record", 1547066350);
            }
        }
    }

    /**
     * Set the label. If `$label` is numeric, use a default label using a predefined prefix
     * and the number given in $label. If `$label` is a string, simply assign it to the `label`
     * property.
     * @param mixed $label
     */
    protected function setLabel($label)
    {
        if (is_string($label)) {
            $this->label = $label;
        } elseif (is_numeric($label)) {
            if ($this->type == self::TYPE_BACKUPCODE) {
                $this->label = $this->getReadableType();
            } else {
                $this->label = sprintf("%s #%s", $this->getReadableType(), $label);
            }
        }
    }
}
