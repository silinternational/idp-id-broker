<?php

namespace common\helpers;

class MySqlDateTime
{
    public const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';
    public const MYSQL_DATE_FORMAT     = 'Y-m-d';
    public const HUMAN_DATE_FORMAT     = 'F jS, Y'; // November 27th, 2017

    public static function now(): string
    {
        return gmdate(self::MYSQL_DATETIME_FORMAT);
    }

    public static function today(): string
    {
        return gmdate(self::MYSQL_DATE_FORMAT);
    }

    public static function formatDate(int $timestamp): string
    {
        return gmdate(self::MYSQL_DATE_FORMAT, $timestamp);
    }

    public static function formatDateTime(int $timestamp): string
    {
        return gmdate(self::MYSQL_DATETIME_FORMAT, $timestamp);
    }

    /**
     * Get a relative date based on given string
     * @param string $difference
     * @return string
     */
    public static function relative(string $difference = '+30 days'): string
    {
        $difference = str_replace('--', '+', $difference);
        return self::formatDate(strtotime($difference));
    }

    /**
     * Get a relative date-time based on given string
     * @param string $difference
     * @return string
     */
    public static function relativeTime(string $difference = '+30 minutes'): string
    {
        return self::formatDateTime(strtotime($difference));
    }

    /**
     * Format timestamp to human friendly date
     * @param string|null $timestamp
     * @return string
     * @throws \Exception if an invalid timestamp is provided
     */
    public static function formatDateForHumans(string $timestamp = null)
    {
        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        $formattedDate = gmdate(self::HUMAN_DATE_FORMAT, $timestamp);

        if ($formattedDate === false) {
            throw new \Exception('invalid timestamp provided');
        }

        return $formattedDate;
    }

    /**
     * Whether the given date falls in the last X days
     *
     * @param string $dbDate formated datetime from database
     * @param int $recentDays
     * @return bool
     */
    public static function dateIsRecent(string $dbDate, int $recentDays)
    {
        $dtInterval = '-' . $recentDays . ' days';
        $recentDate = self::relative($dtInterval);

        return strtotime($dbDate) >= strtotime($recentDate);
    }

    /**
     * Whether the given date-and-time qualifies as "recent" according to the
     * given timeframe.
     *
     * @param string $dbDate formated datetime from database
     * @param string $timeframe -- Example: '11 hours'
     * @return bool
     */
    public static function dateTimeIsRecent(string $dbDate, string $timeframe)
    {
        $dtInterval = '-' . $timeframe;
        $recentDateTime = self::relativeTime($dtInterval);

        return strtotime($dbDate) >= strtotime($recentDateTime);
    }

    /**
     * Compare a date or datetime in MySQL format (yyyy-mm-dd or yyyy-mm-dd hh:mm::ss)
     * to an epoch time as returned from time().
     * Returns true if $eventTime is the same day or before $now.
     *
     * @param string $eventTime
     * @param int $now
     * @return bool
     * @throws \Exception
     */
    public static function isBefore(string $eventTime, int $now)
    {
        $eventTimeEpoch = strtotime($eventTime);
        if ($eventTimeEpoch === false) {
            throw new \Exception('could not interpret time string');
        }

        return !($eventTimeEpoch > $now);
    }

    /**
     * Essentially an alias for `isBefore` to improve code readability
     *
     * @param int $now
     * @param string $eventTime
     * @return bool
     * @throws \Exception
     */
    public static function isAfter(int $now, string $eventTime)
    {
        return self::isBefore($eventTime, $now);
    }
}
