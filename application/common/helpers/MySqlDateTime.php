<?php
namespace common\helpers;

class MySqlDateTime
{
    const MYSQL_FORMAT = 'Y-m-d H:i:s';

    public static function now()
    {
        return gmdate(self::MYSQL_FORMAT);
    }

    public static function format(int $timestamp)
    {
        return gmdate(self::MYSQL_FORMAT, $timestamp);
    }
}
