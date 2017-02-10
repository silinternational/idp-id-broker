<?php
namespace common\helpers;

use Ramsey\Uuid;

class Utils
{
    const DT_FMT = 'Y-m-d H:i:s';

    /**
     * Get a Type 4 UUID
     * @return string
     */
    public static function genUuid()
    {
        return Uuid::uuid4()->toString();
    }
}