<?php
namespace common\helpers;

class MySqlDateTime
{
    public static function now()
    {
        return gmdate('Y-m-d H:i:s');
    }
}
