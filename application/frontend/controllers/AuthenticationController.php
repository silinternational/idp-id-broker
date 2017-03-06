<?php
namespace frontend\controllers;

use common\models\User;
use frontend\components\BaseRestController;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;

class AuthenticationController extends BaseRestController
{
    /**
     * Authenticates the given user based on his/her password
     *
     * @return User upon successful authentication, i.e., "creation".
     * @throws HttpException
     */
    public function actionCreate(): User
    {
        $user = User::findOne([
            'username' => \Yii::$app->request->getBodyParam('username')
        ]);

        if ($user === null) {
            //TODO: is ok to be divulging this user wasn't found?  I think so but verify with the team.
            throw new NotFoundHttpException();
        }

        $user->scenario = User::SCENARIO_AUTHENTICATE;

        $user->attributes = \Yii::$app->request->getBodyParams();

        if($user->validate()) {
            return $user;
        }

        throw new BadRequestHttpException(current($user->getFirstErrors()));
    }
}
