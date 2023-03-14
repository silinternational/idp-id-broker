<?php
namespace common\helpers;

use Br33f\Ga4\MeasurementProtocol\Dto\Request\BaseRequest;
use Br33f\Ga4\MeasurementProtocol\Service;
use yii\base\Security;
use yii\helpers\Html;
use yii\validators\EmailValidator;
use yii\web\BadRequestHttpException;

class Utils
{
    const FRIENDLY_DT_FORMAT = 'l F j, Y g:iA T';
    const DT_ISO8601 = 'Y-m-d\TH:i:s\Z';

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
     * @param integer|string|null $timestamp time as unix timestamp, MYSQL datetime. If omitted,
     *        the current time is used.
     * @return string date in ISO8601 format (e.g. 2019-01-08T12:54:00Z)
     * @throws \Exception if a badly-formatted time string is provided in $timestamp
     */
    public static function getIso8601($timestamp = null)
    {
        $timestamp = $timestamp ?? time();
        $timestamp = is_int($timestamp) ? $timestamp : strtotime($timestamp);
        if ($timestamp === false) {
            throw new \Exception('Unable to parse date to timestamp', 1546977533);
        }
        $dt = date_create_from_format('U', $timestamp);
        return $dt->format(self::DT_ISO8601);
    }

    /**
     * @param string $email an email address
     * @return string with most letters changed to asterisks
     * @throws BadRequestHttpException
     */
    public static function maskEmail($email)
    {
        if (empty($email)) {
            return '';
        }

        $validator = new EmailValidator();
        if (! $validator->validate($email)) {
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
        $domainParts = explode('.', $domain);
        $countParts = count($domainParts);

        // Leave the last part for later, to avoid adding a '.' after it.
        for ($i = 0; $i < $countParts - 1; $i++) {
            $nextPart = $domainParts[$i];
            $newEmail .= substr($nextPart, 0, 1);
            $newEmail .= str_repeat('*', strlen($nextPart) - 1);
            $newEmail .= '.';
        }

        $nextPart = $domainParts[$countParts - 1];
        $newEmail .= substr($nextPart, 0, 1);
        $newEmail .= str_repeat('*', strlen($nextPart) - 1);

        return $newEmail;
    }

    /**
     * @param string $name the name of the event
     */
    public static function GoogleAnalyticsServiceAndRequest(string $callerName)
    {
        $ga4ApiSecret = \Yii::$app->params['googleAnalytics4']['apiSecret']; // 'aB-abcdef7890123456789'
        if ($ga4ApiSecret === null) {
            \Yii::warning(['google-analytics4' => "Aborting GA4 $callerName since the config has no GA4 apiSecret"]);
            return [null, null];
        }

        $ga4MeasurementID = \Yii::$app->params['googleAnalytics4']['measurementId']; // 'G-ABCDE67890'
        if ($ga4MeasurementID === null) {
            \Yii::warning(['google-analytics4' => "Aborting GA4 $callerName since the config has no GA4 measurementId"]);
            return [null, null];
        }

        $ga4ClientId = \Yii::$app->params['googleAnalytics4']['clientId']; // 'IDP_ID_BROKER_LOCALHOST'
        if ($ga4ClientId === null) {
            \Yii::warning(['google-analytics4' => "Aborting GA4 $callerName since the config has no GA4 clientId"]);
            return [null, null];
        }
        $ga4Service = new Service($ga4ApiSecret, $ga4MeasurementID);
        $ga4Request = new BaseRequest($ga4ClientId);
        return [$ga4Service, $ga4Request];
    }
}
