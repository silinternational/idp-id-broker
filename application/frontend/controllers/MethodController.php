<?php

namespace frontend\controllers;

use common\exceptions\InvalidCodeException;
use common\models\Method;
use common\models\User;
use frontend\components\BaseRestController;
use Throwable;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\HttpException;
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

        return Method::findAll(['user_id' => $user->id]);
    }

    /**
     * Retrieve the Method record referenced by the uid and employee_id
     * @param string $uid
     * @param bool $verifiedOnly Limit query to verified methods only
     * @return Method
     * @throws NotFoundHttpException
     */
    protected function getRequestedMethod($uid, $verifiedOnly = false)
    {
        $employeeId = \Yii::$app->request->getBodyParam('employee_id');

        $user = User::findOne(['employee_id' => $employeeId]);

        $query = ['uid' => $uid, 'user_id' => ($user->id ?? null)];
        if ($verifiedOnly) {
            $query['verified'] = 1;
        }
        $method = Method::findOne($query);
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
    public function actionView($uid)
    {
        return $this->getRequestedMethod($uid, true);
    }

    /**
     * Create new password recovery method, normally un-verified, and send a
     * verification message to the user. If 'created' parameter is specified,
     * then the record is created pre-verified and no message is sent to the
     * user.
     *
     * @return Method
     * @throws BadRequestHttpException
     * @throws ConflictHttpException
     * @throws \Exception
     */
    public function actionCreate()
    {
        $value = \Yii::$app->request->post('value');
        if (!is_string($value)) {
            throw new BadRequestHttpException(
                'value is required',
                1541627665
            );
        }

        $employeeId = \Yii::$app->request->getBodyParam('employee_id');
        if ($employeeId === null) {
            throw new BadRequestHttpException(
                'employee_id is required',
                1540990164
            );
        }

        $userId = User::findOne(['employee_id' => $employeeId])->id ?? null;
        if ($userId == null) {
            throw new NotFoundHttpException(
                'employee_id not found',
                1540990165
            );
        }

        return Method::findOrCreate($userId, $value);
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
    public function actionVerify($uid)
    {
        /** @var Method $method */
        $method = Method::findOne(['uid' => $uid]);
        if ($method === null) {
            throw new NotFoundHttpException(
                'method ' . var_export($uid, true) . ' not found',
                1546540650
            );
        }

        if ($method->isVerified()) {
            return $method;
        }

        if ($method->verification_attempts >= \Yii::$app->params['method']['maxAttempts']) {
            throw new TooManyRequestsHttpException();
        }

        $code = \Yii::$app->request->getBodyParam('code');
        if ($code === null) {
            throw new BadRequestHttpException(\Yii::t('app', 'Code is required'));
        }

        try {
            $method->validateProvidedCode($code);
        } catch (InvalidCodeException $e) {
            throw new BadRequestHttpException(\Yii::t('app', 'Invalid verification code'), 1470315942);
        }

        if ($method->isVerificationExpired()) {
            $method->restartVerification();
            throw new HttpException(410);
        }

        try {
            $method->setAsVerified();
        } catch (Throwable $t) {
            $msg = sprintf(
                'Unable to set method as verified (%s:%d): %s',
                $t->getFile(),
                $t->getLine(),
                $t->getMessage()
            );
            Yii::error($msg);
            throw new ServerErrorHttpException(
                'Unable to set method as verified: ' . $t->getMessage(),
                1470315941,
                $t
            );
        }

        return $method;
    }

    /**
     * Delete method
     *
     * @param string $uid
     * @return \stdClass
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws \Throwable
     */
    public function actionDelete($uid)
    {
        $method = $this->getRequestedMethod($uid);

        if (!$method->delete()) {
            throw new ServerErrorHttpException('Unable to delete method', 1540673326);
        }

        \Yii::$app->response->statusCode = 204;
        return new \stdClass();
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

        if ($method->isVerificationExpired()) {
            $method->restartVerification();
        } else {
            $method->sendVerification();
        }

        /*
         * Return empty object
         */
        \Yii::$app->response->statusCode = 204;
        return new \stdClass();
    }
}
