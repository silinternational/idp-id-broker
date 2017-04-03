<?php
namespace frontend\controllers;

use common\models\User;
use frontend\components\BaseRestController;
use Yii;
use yii\web\NotFoundHttpException;

class UserController extends BaseRestController
{
    public function actionIndex() // GET /users
    {
        return User::find()->all();
    }

    public function actionView($id) // GET /users/1
    {
        return User::findOne($id);
    }

    public function actionCreate(): User
    {
        $user = new User();

        $user->attributes = Yii::$app->request->getBodyParams();

        parent::save($user);

        return $user;
    }

    public function actionUpdate($id)
    {
        $user = User::findOne($id);

        if ($user === null) {
            throw new NotFoundHttpException();
        }

        $user->scenario = User::SCENARIO_UPDATE_USER;

        $user->attributes = Yii::$app->request->getBodyParams();

        parent::save($user);

        return $user;
    }

    public function actionUpdatePassword($id): User
    {
        $user = User::findOne($id);

        if ($user === null) {
            throw new NotFoundHttpException();
        }

        $user->scenario = User::SCENARIO_UPDATE_PASSWORD;

        $user->attributes = Yii::$app->request->getBodyParams();

        parent::save($user);

        return $user;
    }
}
