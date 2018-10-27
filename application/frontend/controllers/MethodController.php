<?php
namespace frontend\controllers;

use common\models\Method;
use common\models\User;
use frontend\components\BaseRestController;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/**
 * Class MethodController
 * @package frontend\controllers
 */
class MethodController extends BaseRestController
{
    /**
     * Get list of Recovery Methods for given user
     * @param string $employeeId
     * @return Method[]
     * @throws BadRequestHttpException
     */
    public function actionList(string $employeeId): array
    {
        $user = User::findOne(['employee_id' => $employeeId]);
        if ($user === null) {
            throw new BadRequestHttpException(
                'User not found for employeeId ' . var_export($employeeId, true),
                1540491109
            );
        }

        return Method::findAll(['user_id' => $user->id, 'verified' => 1]);
    }

    /**
     * View single Recovery Method
     * @param string $uid
     * @return Method
     * @throws NotFoundHttpException
     */
    public function actionView($uid)
    {
        $employeeId = \Yii::$app->request->getBodyParam('employee_id');

        $user = User::findOne(['employee_id' => $employeeId]);

        $method = Method::findOne(['uid' => $uid, 'user_id' => ($user->id ?? null)]);
        if ($method === null) {
            throw new NotFoundHttpException(
                'method ' . var_export($uid, true)
                . ' for employee ' . var_export($employeeId, true)
                . ' not found', 1540665086 
            );
        }

        return $method;
    }

    /**
     * Delete method
     *
     * @param string $uid
     * @return array
     * @throws ServerErrorHttpException
     */
    public function actionDelete($uid)
    {
        $method = $this->actionView($uid);

        if ( ! $method->delete()) {
            throw new ServerErrorHttpException('Unable to delete method', 1540673326);
        }

        return [];
    }

}
