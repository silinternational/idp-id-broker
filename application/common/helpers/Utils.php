<?php
namespace common\helpers;

use yii\base\Security;

class Utils
{
    const FRIENDLY_DT_FORMAT = 'l F j, Y g:iA T';

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
     */
    public static function getRandomDigits($length = 4)
    {
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= random_int(0, 9);
        }
        return $result;
    }

    /**
     * Return human readable date time
     * @param int|string|null $timestamp Either a unix timestamp or a date in string format
     * @return string
     * @throws \Exception
     */
    public static function getFriendlyDate($timestamp = null)
    {
        $timestamp = $timestamp ?? time();
        $timestamp = is_int($timestamp) ? $timestamp : strtotime($timestamp);
        if ($timestamp === false) {
            throw new \Exception('Unable to parse date to timestamp', 1468865838);
        }
        return date(self::FRIENDLY_DT_FORMAT, $timestamp);
    }


    /**
     * @param string $email an email address
     * @return string with most letters changed to asterisks
     * @throws BadRequestHttpException
     */
    public static function maskEmail($email)
    {
        $validator = new EmailValidator();
        if ( ! $validator->validate($email)) {
            \Yii::warning([
                'action' => 'mask email',
                'status' => 'error',
                'error' => 'Invalid email address provided: ' . Html::encode($email),
            ]);
            throw new BadRequestHttpException('Invalid email address provided.', 1461459797);
        }

        list($part1, $domain) = explode('@', $email);
        $newEmail = '';
        $useRealChar = true;

        /*
         * Replace all characters with '*', except the first one, the last one,
         * underscores, and each character that follows an underscore.
         */
        foreach (str_split($part1) as $nextChar) {
            if ($useRealChar) {
                $newEmail .= $nextChar;
                $useRealChar = false;
            } elseif ($nextChar === '_') {
                $newEmail .= $nextChar;
                $useRealChar = true;
            } else {
                $newEmail .= '*';
            }
        }

        // replace the last * with the last real character
        $newEmail = substr($newEmail, 0, -1);
        $newEmail .= substr($part1, -1);
        $newEmail .= '@';

        /*
         * Add an '*' for each of the characters of the domain, except
         * for the first character of each part and the '.'
         */
        list($domainA, $domainB) = explode('.', $domain);

        $newEmail .= substr($domainA, 0, 1);
        $newEmail .= str_repeat('*', strlen($domainA) - 1);
        $newEmail .= '.';

        $newEmail .= substr($domainB, 0, 1);
        $newEmail .= str_repeat('*', strlen($domainB) - 1);
        return $newEmail;
    }
}
