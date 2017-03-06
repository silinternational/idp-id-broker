<?php
namespace frontend\controllers;

use common\models\PasswordHistory;
use common\models\User;
use frontend\components\BaseRestController;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;

class UserController extends BaseRestController
{
    /**
     * Create a new User.  If User already exists, updates will occur but only for the
     * fields that would've been accepted during a normal creation.
     *
     * @throws HttpException
     */
    public function actionCreate(): User
    {
        $existingUser = User::findOne([
            'employee_id' => \Yii::$app->request->getBodyParam('employee_id')
        ]);

        $user = $existingUser ?? new User();

        $user->attributes = \Yii::$app->request->getBodyParams();

        parent::save($user);

        return $user;
    }

    /**
     * Change a specific user's password.
     *
     * @throws HttpException
     */
    public function actionUpdatePassword(string $employeeId): User
    {
        $user = User::findOne([
            'employee_id' => $employeeId
        ]);

        if ($user === null) {
            //TODO: is ok to be divulging this user wasn't found?  I think so but verify with the team.
            throw new NotFoundHttpException();
        }

        $user->scenario = User::SCENARIO_UPDATE_PASSWORD;

        $previous = $user->savePassword(\Yii::$app->request->getBodyParam('password'));

        if (empty($previous)) {
            parent::save($user);
        } else {
            $history = new PasswordHistory($user->id, $previous);

            parent::save($user, $history);
        }

        return $user;
    }
}
