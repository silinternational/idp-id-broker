<?php
namespace common\models;

use common\components\MfaBackendInterface;
use common\helpers\MySqlDateTime;
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

    public function rules(): array
    {
        return ArrayHelper::merge([
            [
                'created_utc', 'default', 'value' => MySqlDateTime::now(),
            ],
            [
                'type', 'in', 'range' => [self::TYPE_TOTP, self::TYPE_U2F, self::TYPE_BACKUPCODE]
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
                return date('c', strtotime($model->created_utc));
            },
            'last_used_utc' => function($model) {
                if ($model->last_used_utc !== null) {
                    return date('c', strtotime($model->last_used_utc));
                }
                return null;
            },
            'data' => function($model) {
                $data = [];
                /** @var Mfa $model */
                if ($model->verified === 1 && $model->scenario === User::SCENARIO_AUTHENTICATE) {
                    $data += $model->authInit();
                }
                if ($model->type === self::TYPE_BACKUPCODE) {
                    $data += ['count' => count($model->mfaBackupcodes)];
                }
                return $data;
            }
        ];
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
    }

    /**
     * Check if given type is valid
     * @param string $type
     * @return bool
     */
    public static function isValidType(string $type): bool
    {
        if (in_array($type, [self::TYPE_BACKUPCODE, self::TYPE_U2F, self::TYPE_TOTP])) {
            return true;
        }
        return false;
    }

    /**
     * @param string $type
     * @return MfaBackendInterface
     */
    public static function getBackendForType(string $type): MfaBackendInterface
    {
        switch ($type) {
            case self::TYPE_BACKUPCODE:
                return \Yii::$app->backupcode;
            case self::TYPE_TOTP:
                return \Yii::$app->totp;
            case self::TYPE_U2F:
                return \Yii::$app->u2f;
        }
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

        $mfa = new Mfa();

        /*
         * User can only have one 'backupcode' type, so if already exists, use existing
         */
        if ($type == self::TYPE_BACKUPCODE) {
            $existing = self::findOne(['user_id' => $userId, 'type' => self::TYPE_BACKUPCODE]);
            if ($existing instanceof Mfa) {
                $mfa = $existing;
            }
        }

        $mfa->user_id = $userId;
        $mfa->type = $type;
        $mfa->verified = ($type == self::TYPE_BACKUPCODE) ? 1 : 0;

        if (empty($label)) {
            $existingCount = count($user->mfas);
            $label = sprintf("2SV #%s", $existingCount+1);
        }
        $mfa->label = $label;

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
     * Remove records that were not verified within the given time frame
     * @param int $maxAgeHours
     */
    public static function removeOldUnverifiedRecords($maxAgeHours = 2)
    {
        $removeOlderThan = MySqlDateTime::relative('-' . $maxAgeHours . ' hours');
        $mfas = self::find()->where(['verified' => 0])
            ->andWhere(['<', 'created_utc', $removeOlderThan])->all();

        foreach ($mfas as $mfa) {
            if ($mfa->delete() === false) {
                \Yii::error([
                    'action' => 'delete old unverified mfa records',
                    'status' => 'error',
                    'error' => $mfa->getFirstErrors(),
                    'mfa_id' => $mfa->id,
                ]);
            }
        }
    }
}