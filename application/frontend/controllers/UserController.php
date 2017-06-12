<?php
namespace frontend\controllers;

use common\helpers\MySqlDateTime;
use common\models\User;
use frontend\components\BaseRestController;
use Yii;
use yii\data\ActiveDataProvider;

class UserController extends BaseRestController
{
    public function actionIndex() // GET /user
    {
        /* NOTE: Return a DataProvider here (rather than an array of Models) so
         *       that the Serializer can limit the fields returned if a 'fields'
         *       query string parameter is present requesting only certain
         *       fields.  */
        return new ActiveDataProvider([
            'query' => User::find(),
            'pagination' => false,
        ]);
    }

    public function actionView(string $employeeId) // GET /user/abc123
    {
        $user = User::findOne(['employee_id' => $employeeId]);

        if (isset($user)) {
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

    public function actionExpiring(): array
    {
        return User::getExpiringUsers(Yii::$app->request->queryParams);
    }

    public function actionNew(): array
    {
        $createdOn = Yii::$app->request->queryParams['created_on'] ?? MySqlDateTime::today();

        return User::getNewUsers($createdOn);
    }
}
