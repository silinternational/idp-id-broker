<?php
namespace frontend\controllers;

use common\models\Method;
use common\models\User;
use frontend\components\BaseRestController;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
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
                . ' not found',
                1540665086
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

    /**
     * Create new unverified method. Also sends verification message.
     * @return Method
     * @throws BadRequestHttpException
     * @throws ConflictHttpException
     * @throws \Exception
     */
    public function actionCreate()
    {
        // ensure we don't use expired methods
        $this->deleteExpiredUnverifiedMethods();

        $value = mb_strtolower(\Yii::$app->request->post('value'));
        $userId = User::findOne(['employee_id' => $employeeId])->id ?? null;
        $method = Method::findOne(['value' => $value, 'user_id' => $userId]);

        if ($method === null) {
            $method = new Method;
            $method->user_id = $userId;
            $method->value = mb_strtolower($value);
        }

        $method->sendVerification();

        if ( ! $method->save()) {
            throw new ServerErrorHttpException(
                sprintf('Unable to save method after sending verification message'),
                1461441851
            );
        }

        return $method;
    }
}
