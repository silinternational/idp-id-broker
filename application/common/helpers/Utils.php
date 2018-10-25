<?php
namespace common\helpers;

use yii\base\Security;

class Utils
{

    /**
     * @param int $length
     * @return string
     */
    public static function generateRandomString($length = 32)
    {
        $security = new Security();
        return $security->generateRandomString($length);
    }

    /**
     * Return a random string of numbers
     * @param int $length [default=4]
     * @return string
     * @throws \Exception
     */
    public static function getRandomDigits($length = 4)
    {
        $result = '';
        while (strlen($result) < $length) {
            $randomString = openssl_random_pseudo_bytes(16, $cryptoStrong);
            if ($cryptoStrong !== true) {
                throw new \Exception('Unable to generate cryptographically strong number', 1460385230);
            } else if ( ! $randomString) {
                throw new \Exception('Unable to generate random number', 1460385231);
            }

            $hex = bin2hex($randomString);
            $digits = preg_replace('/[^0-9]/', '', $hex);
            $result .= $digits;
        }

        $randomDigits = substr($result, 0, $length);

        return $randomDigits;
    }


}
