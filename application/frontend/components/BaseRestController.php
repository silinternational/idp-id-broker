<?php
namespace frontend\components;

use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;

class BaseRestController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Use request header-> 'Authorization: Bearer <token>'
        $behaviors['authenticator']['class'] = HttpBearerAuth::className();

        return $behaviors;
    }
}
