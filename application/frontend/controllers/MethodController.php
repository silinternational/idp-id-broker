<?php
namespace frontend\controllers;

use common\exceptions\InvalidCodeException;
use common\models\Method;
use common\models\User;
use frontend\components\BaseRestController;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\TooManyRequestsHttpException;

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
     * Retrieve the Method record referenced by the uid and employee_id
     * @param string $uid
     * @return Method
     * @throws NotFoundHttpException
     */
    protected function getRequestedMethod($uid)
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
     * View single Recovery Method
     * @param string $uid
     * @return Method
     * @throws NotFoundHttpException
     */
    public function getView($uid)
    {
        return $this->getRequestedMethod($uid);
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
        Method::deleteExpiredUnverifiedMethods();

        $value = mb_strtolower(\Yii::$app->request->post('value'));
        $employeeId = \Yii::$app->request->getBodyParam('employee_id');
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

    /**
     * Validates user submitted code and marks method as verified if valid
     * @param string $uid
     * @return Method
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws TooManyRequestsHttpException
     * @throws \Exception
     */
    public function actionUpdate($uid)
    {
        Method::deleteExpiredUnverifiedMethods();

        /** @var Method $method */
        $method = $this->getRequestedMethod($uid);

        if ($method->isVerified()) {
            return $method;
        }

        if ($method->verification_attempts >= \Yii::$app->params['reset']['maxAttempts']) {
            throw new TooManyRequestsHttpException();
        }

        $code = \Yii::$app->request->getBodyParam('code');
        if ($code === null) {
            throw new BadRequestHttpException(\Yii::t('app', 'Code is required'));
        }

        try {
            $method->validateAndSetAsVerified($code);
        } catch (InvalidCodeException $e) {
            throw new BadRequestHttpException(\Yii::t('app', 'Invalid verification code'), 1470315942);
        } catch (\Exception $e) {
            throw new ServerErrorHttpException(
                'Unable to set method as verified: ' . $e->getMessage(),
                1470315941,
                $e
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
        $method = $this->getRequestedMethod($uid);

        if ( ! $method->delete()) {
            throw new ServerErrorHttpException('Unable to delete method', 1540673326);
        }

        return [];
    }

    /**
     * @param string $uid
     * @return \stdClass
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     * @throws \Exception
     */
    public function actionResend($uid)
    {
        $method = $this->getRequestedMethod($uid);

        if ($method->isVerified()) {
            throw new BadRequestHttpException(\Yii::t('app', 'Method already verified'));
        }

        $method->sendVerification();

        /*
         * Return empty object
         */
        return new \stdClass();
    }
}
