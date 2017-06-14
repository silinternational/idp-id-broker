<?php
namespace common\helpers;

class MySqlDateTime
{
    const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';
    const MYSQL_DATE_FORMAT     = 'Y-m-d';

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
}
