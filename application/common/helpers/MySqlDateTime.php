<?php
namespace common\helpers;

class MySqlDateTime
{
    const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';
    const MYSQL_DATE_FORMAT     = 'Y-m-d';
    const HUMAN_DATE_FORMAT     = 'F jS, Y'; // November 27th, 2017

    public static function now()
    {
        return gmdate(self::MYSQL_DATETIME_FORMAT);
    }

    public static function today()
    {
        return gmdate(self::MYSQL_DATE_FORMAT);
    }

    public static function formatDate(int $timestamp)
    {
        return gmdate(self::MYSQL_DATE_FORMAT, $timestamp);
    }

    public static function formatDateTime(int $timestamp)
    {
        return gmdate(self::MYSQL_DATETIME_FORMAT, $timestamp);
    }

    /**
     * Get a relative date based on given string
     * @param string $difference
     * @return false|string
     */
    public static function relative(string $difference = '+30 days')
    {
        return self::formatDate(strtotime($difference));
    }
    
    /**
     * Get a relative date-time based on given string
     * @param string $difference
     * @return false|string
     */
    public static function relativeTime(string $difference = '+30 minutes')
    {
        return self::formatDateTime(strtotime($difference));
    }

    /**
     * Format timestamp to human friendly date
     * @param string|null $timestamp
     * @return false|string
     */
    public static function formatDateForHumans(string $timestamp = null)
    {
        if ( ! is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        return gmdate(self::HUMAN_DATE_FORMAT, $timestamp);
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

        $dateFromDb = DateTime::CreateFromFormat(self::MYSQL_DATETIME_FORMAT, $dbDate);

        return $dbDate >= $dateFromDb;
    }
}
