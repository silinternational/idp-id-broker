<?php
namespace frontend\controllers;

use common\models\User;
use frontend\components\BaseRestController;
use Yii;
use yii\web\NotFoundHttpException;

class UserController extends BaseRestController
{
    public function actionIndex()
    {
        return User::find()->all();
    }

    public function actionCreate(): User
    {
        $existingUser = User::findOne([
            'employee_id' => Yii::$app->request->getBodyParam('employee_id')
        ]);

        $user = $existingUser ?? new User();

        $user->attributes = Yii::$app->request->getBodyParams();

        parent::save($user);

        return $user;
    }

    public function actionUpdatePassword(string $employeeId): User
    {
        $user = User::findOne([
            'employee_id' => $employeeId
        ]);

        if ($user === null) {
            throw new NotFoundHttpException();
        }

        $user->scenario = User::SCENARIO_UPDATE_PASSWORD;

        $user->attributes = Yii::$app->request->getBodyParams();

        parent::save($user);

        return $user;
    }
}
