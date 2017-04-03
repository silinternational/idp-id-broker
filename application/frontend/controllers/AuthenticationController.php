<?php
namespace frontend\controllers;

use common\models\User;
use frontend\components\BaseRestController;
use Yii;
use yii\web\BadRequestHttpException;

class AuthenticationController extends BaseRestController
{
    /**
     * Authenticates the given user based on his/her password
     *
     * @return User upon successful authentication, i.e., "creation".
     * @throws BadRequestHttpException
     */
    public function actionCreate(): User
    {
        $user = User::findOne([
            'username' => (string)Yii::$app->request->getBodyParam('username')
        ]) ?? new User();

        $user->scenario = User::SCENARIO_AUTHENTICATE;

        $user->attributes = Yii::$app->request->getBodyParams();

        if ($user->validate()) {
            return $user;
        }

        throw new BadRequestHttpException();
    }
}
