<?php
namespace common\models;

use yii\base\Component;
use yii\web\IdentityInterface;

class ApiConsumer extends Component implements IdentityInterface
{
    public static function findIdentityByAccessToken($token, $type = null)
    {
        if ($token === getenv('API_ACCESS_KEY')) {
            return new ApiConsumer();
        }

        return null;
    }
    
    public static function findIdentity($id)
    {
        return null;
    }
    
    public function getId()
    {
        return null;
    }

    public function getAuthKey()
    {
        return null;
    }

    public function validateAuthKey($authKey)
    {
        return false;
    }
}
