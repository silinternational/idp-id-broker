<?php

namespace common\models;

use Yii;
use yii\base\Component;
use yii\web\IdentityInterface;

class ApiConsumer extends Component implements IdentityInterface
{
    public static function findIdentityByAccessToken($token, $type = null)
    {
        if (self::isAuthorized($token)) {
            return new ApiConsumer();
        }

        Yii::warning("unrecognized token: $token");

        return null;
    }

    private static function isAuthorized($token): bool
    {
        return in_array($token, Yii::$app->params['authorizedTokens'], true);
    }

    public static function findIdentity($id)
    {
        // since this app is a stateless RESTful app, this is not applicable
        return null;
    }

    public function getId()
    {
        // since this app is a stateless RESTful app, this is not applicable
        return null;
    }

    public function getAuthKey()
    {
        // since this app is a stateless RESTful app, this is not applicable (no cookies)
        return null;
    }

    public function validateAuthKey($authKey)
    {
        // since this app is a stateless RESTful app, this is not applicable (no cookies)
        return false;
    }
}
