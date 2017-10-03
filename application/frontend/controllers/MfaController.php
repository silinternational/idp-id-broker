<?php
namespace frontend\controllers;

use common\components\MfaBackendInterface;
use common\models\Mfa;
use common\models\User;
use frontend\components\BaseRestController;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class MfaController extends BaseRestController
{

    /**
     * Create new MFA record
     * @return array
     * @throws BadRequestHttpException
     */
    public function actionCreate()
    {
        $req = \Yii::$app->request;
        $type = $req->getBodyParam('type','invalid');
        if ( ! Mfa::isValidType($type)) {
            throw new BadRequestHttpException('The provided type is not a supported MFA type', 1506695647);
        }

        $employeeId = $req->getBodyParams('employee_id');
        if ( is_null($employeeId)) {
            throw new BadRequestHttpException('employee_id is required', 1506695722);
        }

        $user = User::findOne(['employee_id' => $employeeId]);
        if ($user === null) {
            throw new BadRequestHttpException('User not found', 1506695733);
        }

        /** @var MfaBackendInterface $mfaBackend */
        $mfaBackend = $this->getBackendForType($type);

        return $mfaBackend->regInit($user->id);
    }

    /**
     * Verify value with MFA backend
     * @param int $id
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @return User
     */
    public function actionVerify(int $id)
    {
        $req = \Yii::$app->request;
        $value = $req->getBodyParam('value');
        if ($value === null) {
            throw new BadRequestHttpException('Value is required for verification', 1506697678);
        }

        $employeeId = $req->getBodyParam('employee_id');
        if ($employeeId == null) {
            throw new BadRequestHttpException("employee_id is required");
        }

        $user = User::findOne(['employee_id' => $employeeId]);
        if ($user == null) {
            throw new BadRequestHttpException("Invalid employee_id");
        }

        $mfa = Mfa::findOne(['id' => $id, 'user_id' => $user->id]);
        if ($mfa === null) {
            throw new NotFoundHttpException(
                sprintf('MFA record for id %s not found', $id),
                1506697604
            );
        }

        // Strip spaces from $value
        $value = str_replace(' ', '', $value);

        $mfaBackend = $this->getBackendForType($mfa->type);
        if ( !  $mfaBackend->verify($mfa->id, $value)){
            throw new BadRequestHttpException();
        }

        return $mfa->user;
    }

    /**
     * Get list of MFA backends for given user
     * @param string $employeeId
     * @return Mfa[]
     * @throws BadRequestHttpException
     */
    public function actionList(string $employeeId): array
    {
        $user = User::findOne(['employee_id' => $employeeId]);
        if ($user === null) {
            throw new BadRequestHttpException(
                sprintf('User not found for employeeId %s', $employeeId),
                1506697453
            );
        }

        return Mfa::findAll(['user_id' => $user->id, 'verified' => 1]);
    }

    /**
     * Delete MFA record
     * @param int $id
     * @return false|int
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function actionDelete(int $id)
    {
        $employeeId = \Yii::$app->request->getBodyParam('employee_id');
        if ($employeeId == null) {
            throw new BadRequestHttpException("employee_id is required");
        }

        $user = User::findOne(['employee_id' => $employeeId]);
        if ($user == null) {
            throw new BadRequestHttpException("Invalid employee_id");
        }

        $mfa = Mfa::findOne(['id' => $id, 'user_id' => $user->id]);
        if ($mfa === null) {
            throw new NotFoundHttpException(
                sprintf('MFA record for id %s not found', $id),
                1506697614
            );
        }

        $mfaBackend = $this->getBackendForType($mfa->type);
        $mfaBackend->delete($mfa->id);
    }

    /**
     * @param string $type
     * @return MfaBackendInterface
     */
    private function getBackendForType(string $type): MfaBackendInterface
    {
        switch ($type) {
            case Mfa::TYPE_BACKUPCODE:
                return\Yii::$app->backupcode;
            case Mfa::TYPE_TOTP:
                return \Yii::$app->totp;
            case Mfa::TYPE_U2F:
                return \Yii::$app->u2f;
        }
    }
}