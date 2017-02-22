<?php
namespace common\helpers;


use Ramsey\Uuid\Uuid;

class Utils
{
    const DT_FMT = 'Y-m-d H:i:s';

    public static function genUuid()
    {
        return Uuid::uuid4()->toString();
    }
}
