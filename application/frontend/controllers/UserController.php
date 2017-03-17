<?php
namespace frontend\controllers;

use Yii;
use common\models\PasswordHistory;
use common\models\User;
use frontend\components\BaseRestController;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;

class UserController extends BaseRestController
{
    /**
     * Creates a new user or updates an existing user if matched on the employee id.
     *
     * @throws HttpException
     */
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

    /**
     * Change a specific user's password.
     *
     * @param string $employeeId
     * @return User
     * @throws NotFoundHttpException
     */
    public function actionUpdatePassword(string $employeeId): User
    {
        $user = User::findOne([
            'employee_id' => $employeeId
        ]);

        if ($user === null) {
            throw new NotFoundHttpException();
        }

        $user->scenario = User::SCENARIO_UPDATE_PASSWORD;

//TODO: rework, move into User.
//TODO: build in rule, can't one of last 10 passwords.
        $previous = $user->hashPassword(Yii::$app->request->getBodyParam('password'));

        if (empty($previous)) {
            parent::save($user);
        } else {
            $history = new PasswordHistory($user->id, $previous);

            parent::save($user, $history);
        }

        return $user;
    }
}
