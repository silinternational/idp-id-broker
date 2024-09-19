<?php

namespace frontend\controllers;

use common\models\User;
use frontend\components\BaseRestController;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UnprocessableEntityHttpException;

class UserController extends BaseRestController
{
    public function actionIndex() // GET /user
    {
        return User::search(Yii::$app->request->queryParams);
    }

    public function actionView(string $employeeId) // GET /user/abc123
    {
        $user = User::findOne(['employee_id' => $employeeId]);

        if (isset($user)) {
            $user->loadMfaData('');
            return $user;
        }

        Yii::$app->response->statusCode = 204;

        return null;
    }

    public function actionCreate(): User
    {
        $user = new User();

        $user->scenario = User::SCENARIO_NEW_USER;

        $user->attributes = Yii::$app->request->getBodyParams();

        $this->save($user);

        /*
         * Refresh user model to retrieve database default values
         */
        $user->refresh();

        Yii::info([
            'action' => 'user/create',
            'status' => 'created',
            'id' => $user->id,
            'employeeId' => $user->employee_id,
            'scenario' => $user->scenario,
            'email' => $user->email,
        ], 'application');

        return $user;
    }

    public function actionUpdate(string $employeeId)
    {
        $user = User::findOne(['employee_id' => $employeeId]);

        if ($user === null) {
            Yii::$app->response->statusCode = 204;

            return null;
        }

        $user->scenario = User::SCENARIO_UPDATE_USER;

        $user->attributes = Yii::$app->request->getBodyParams();

        $this->save($user);

        return $user;
    }

    public function actionUpdateLastLogin(string $employeeId)
    {
        $user = User::findOne(condition: ['employee_id' => $employeeId]);

        if ($user === null) {
            Yii::$app->response->statusCode = 404;

            return null;
        }

        if (!$user->updateLastLogin()) {
            Yii::$app->response->statusCode = 500;
            return null;
        }

        return ['employee_id' => $user->employee_id, 'last_login_utc' => $user->last_login_utc];
    }

    public function actionUpdatePassword(string $employeeId)
    {
        $user = User::findOne(['employee_id' => $employeeId]);

        if ($user === null) {
            Yii::$app->response->statusCode = 204;

            return null;
        }

        $user->scenario = User::SCENARIO_UPDATE_PASSWORD;

        $user->attributes = Yii::$app->request->getBodyParams();

        $this->save($user);

        return $user;
    }

    /**
     * @param string $employeeId
     * @throws NotFoundHttpException
     * @throws UnprocessableEntityHttpException
     */
    public function actionAssessPassword(string $employeeId)
    {
        $user = User::findOne(['employee_id' => $employeeId]);

        if ($user === null) {
            throw new NotFoundHttpException('User not found');
        }

        if ($user->assessPassword(Yii::$app->request->getBodyParam('password'))) {
            Yii::$app->response->setStatusCode(204);
            return;
        } else {
            $errors = join(', ', $user->getFirstErrors());
            throw new UnprocessableEntityHttpException($errors);
        }
    }
}
