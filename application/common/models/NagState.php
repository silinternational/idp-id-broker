<?php

namespace common\models;

use \Exception;
use common\helpers\MySqlDateTime;

class NagState
{
    const NAG_NONE           = 'none';
    const NAG_ADD_MFA        = 'add_mfa';
    const NAG_ADD_METHOD     = 'add_method';
    const NAG_PROFILE_REVIEW = 'profile_review';

    /** @var string */
    private $state;

    /** @var string */
    private $nagForMfaAfter;

    /** @var string */
    private $nagForMethodAfter;

    /** @var string */
    private $reviewProfileAfter;

    /** @var int */
    private $numberOfVerifiedMfas;

    /** @var int */
    private $numberOfVerifiedMethods;

    public function __construct(
        $nagForMfaAfter,
        $nagForMethodAfter,
        $reviewProfileAfter,
        $numberOfVerifiedMfas,
        $numberOfVerifiedMethods
    ) {
        $state = null;
        $this->nagForMfaAfter = $nagForMfaAfter;
        $this->nagForMethodAfter = $nagForMethodAfter;
        $this->reviewProfileAfter = $reviewProfileAfter;
        $this->numberOfVerifiedMfas = $numberOfVerifiedMfas;
        $this->numberOfVerifiedMethods = $numberOfVerifiedMethods;
    }

    /**
     * @uses isTimeToNagToAddMfa()
     * @uses isTimeToNagToAddMethod()
     * @uses isTimeForReview()
     * @return int|string
     */
    public function getState()
    {
        /*
         * Don't recalculate in case the date has changed since the last calculation.
         */
        if ($this->state !== null) {
            return $this->state;
        }

        $possibleNags = [
            self::NAG_ADD_MFA => 'isTimeToNagToAddMfa',
            self::NAG_ADD_METHOD => 'isTimeToNagToAddMethod',
            self::NAG_PROFILE_REVIEW => 'isTimeForReview',
        ];

        $now = time();
        foreach ($possibleNags as $nagType => $isTime) {
            if ($this->$isTime($now)) {
                $this->state = $nagType;
                return $this->state;
            }
        }
        return self::NAG_NONE;
    }

    /**
     * Based on provided time, determine whether to present a reminder to add
     * an MFA option.
     * @param int $now
     * @return bool
     * @throws Exception
     * @usedby getState()
     */
    private function isTimeToNagToAddMfa(int $now): bool
    {
        return $this->numberOfVerifiedMfas === 0 && MySqlDateTime::isBefore($this->nagForMfaAfter, $now);
    }

    /**
     * Based on provided time, determine whether to present a reminder to add
     * a recovery method option.
     * @param int $now
     * @return bool
     * @throws Exception
     * @usedby getState()
     */
    private function isTimeToNagToAddMethod(int $now): bool
    {
        return $this->numberOfVerifiedMethods === 0 && MySqlDateTime::isBefore($this->nagForMethodAfter, $now);
    }

    /**
     * Based on provided time, determine whether to present a profile review to
     * the user.
     * @param int $now
     * @return bool
     * @throws Exception
     * @usedby getState()
     */
    private function isTimeForReview(int $now)
    {
        return MySqlDateTime::isBefore($this->reviewProfileAfter, $now);
    }
}
