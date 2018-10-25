<?php
namespace frontend\controllers;

use common\models\Method;
use common\models\User;
use frontend\components\BaseRestController;
use yii\web\BadRequestHttpException;

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
                sprintf('User not found for employeeId %s', $employeeId),
                1540491109
            );
        }

        return Method::findAll(['user_id' => $user->id, 'verified' => 1]);
    }

}
