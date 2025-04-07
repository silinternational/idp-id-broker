<?php

namespace frontend\controllers;

use common\models\Mfa;
use common\models\MfaWebauthn;
use common\models\User;
use common\components\MfaBackendWebAuthn;
use frontend\components\BaseRestController;
use stdClass;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\TooManyRequestsHttpException;

class MfaController extends BaseRestController
{
    /**
     * Create new MFA record
     * @return array
     * @throws BadRequestHttpException
     * @throws ConflictHttpException
     * @throws ServerErrorHttpException
     * @throws ForbiddenHttpException
     */
    public function actionCreate()
    {
        $req = \Yii::$app->request;
        $type = $req->getBodyParam('type', 'invalid');
        if (!Mfa::isValidType($type)) {
            throw new BadRequestHttpException('The provided type is not a supported MFA type', 1506695647);
        }

        $employeeId = $req->getBodyParam('employee_id');
        if (is_null($employeeId)) {
            throw new BadRequestHttpException('employee_id is required', 1506695722);
        }

        $user = User::findOne(['employee_id' => $employeeId]);
        if ($user === null) {
            throw new BadRequestHttpException('User not found', 1506695733);
        }

        $label = $req->getBodyParam('label');

        $recoveryEmail = $req->getBodyParam('recovery_email', '');

        // rpOrigin is needed for WebAuthn authentication
        $rpOrigin = urldecode($req->get('rpOrigin', ''));
        if ($rpOrigin != '' && !in_array($rpOrigin, \Yii::$app->params['authorizedRPOrigins'])) {
            throw new ForbiddenHttpException("Invalid rpOrigin: " . $rpOrigin, 1638539433);
        }

        return Mfa::create($user->id, $type, $label, $rpOrigin, $recoveryEmail);
    }

    /**
     * Verify value with MFA backend
     * @param int $id
     * @param string $type (optional) If not blank, it must be `registration`, referring to verifying a webauthn registration
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws TooManyRequestsHttpException
     * @return null
     */
    public function actionVerify(int $id, string $type = "")
    {
        $req = \Yii::$app->request;
        $value = $req->getBodyParam('value');
        if ($value === null) {
            throw new BadRequestHttpException('Value is required for verification', 1506697678);
        }

        $label = $req->getBodyParam('label');
        $label = $label ?: '';

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

        // Only TOTP should provide a string $value, in which case it should just have digits.
        // This removes any non-digits.
        if (is_string($value)) {
            $value = preg_replace('/\D/', '', $value);
        }

        if (is_array($value)) {
            if (isset($value['clientExtensionResults']) && empty($value['clientExtensionResults'])) {
                // Force JSON-encoding to treat this as an empty object, not an empty array.
                $value['clientExtensionResults'] = new stdClass();
            }
        }

        // rpOrigin is needed for WebAuthn authentication
        $rpOrigin = $req->get('rpOrigin', '');
        if ($rpOrigin != '' && !in_array($rpOrigin, \Yii::$app->params['authorizedRPOrigins'])) {
            throw new ForbiddenHttpException("Invalid rpOrigin", 1638539443);
        }

        if (!$mfa->verify($value, $rpOrigin, $type, $label)) {
            throw new BadRequestHttpException();
        }
        $mfa->loadData($rpOrigin);
        return $mfa;
    }

    /**
     * Get list of MFA backends for given user
     * @param string $employeeId
     * @return Mfa[]
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
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

        // rpOrigin is needed for WebAuthn authentication
        $rpOrigin = \Yii::$app->request->get('rpOrigin', '');
        if ($rpOrigin != '' && !in_array($rpOrigin, \Yii::$app->params['authorizedRPOrigins'])) {
            $message = "Invalid rpOrigin. Received " . $rpOrigin . " authorized " .
                var_export(\Yii::$app->params['authorizedRPOrigins'], true);
            \Yii::error($message);
            throw new ForbiddenHttpException($message, 1638378156);
        }

        $mfaOptions = Mfa::findAll(['user_id' => $user->id, 'verified' => 1]);
        foreach ($mfaOptions as $opt) {
            $opt->loadData($rpOrigin);
        }

        return $mfaOptions;
    }



    /**
     * Find an MFA by id and employee_id
     *
     * @param int $id
     * @return Mfa|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    protected function getRequestedMfa($id)
    {
        $employeeId = \Yii::$app->request->getBodyParam('employee_id');
        if ($employeeId == null) {
            throw new BadRequestHttpException("employee_id is required");
        }

        $user = User::findOne(['employee_id' => $employeeId]);
        if ($user == null) {
            \Yii::error([
                'action' => 'find user for mfa',
                'status' => 'error',
                'employee_id' => $employeeId,
                'mfaId' => $id,
                'request' => \Yii::$app->request->url,
                'error' => 'invalid employee_id for requested MFA',
            ]);
            throw new BadRequestHttpException("Invalid employee_id", 1543934333);
        }

        $mfa = Mfa::findOne(['id' => $id, 'user_id' => $user->id]);
        if ($mfa === null) {
            throw new NotFoundHttpException(
                sprintf('MFA record for id %s not found', $id),
                1506697614
            );
        }

        // rpOrigin is needed for WebAuthn authentication
        $rpOrigin = \Yii::$app->request->get('rpOrigin', '');
        if ($rpOrigin != '' && !in_array($rpOrigin, \Yii::$app->params['authorizedRPOrigins'])) {
            throw new ForbiddenHttpException("Invalid rpOrigin", 1638539680);
        }
        $mfa->loadData($rpOrigin);

        return $mfa;
    }

    /**
     * Delete MFA record
     * @param int $id
     * @return null
     * @throws \Throwable
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function actionDelete(int $id)
    {
        $mfa = $this->getRequestedMfa($id);

        if ($mfa->delete() === false) {
            \Yii::error([
                'action' => 'delete mfa',
                'status' => 'error',
                'error' => $mfa->getFirstErrors(),
                'mfa_id' => $mfa->id,
            ]);
            throw new ServerErrorHttpException("Unable to delete MFA option", 1508877279);
        }

        \Yii::$app->response->statusCode = 204;
        return null;
    }

    /**
     * Delete one u2f/webauthn credential from an MFA record
     * @param int $mfaId
     * @param int $webauthnId
     * @return null
     * @throws \Throwable
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function actionDeleteCredential(int $mfaId, int $webauthnId)
    {
        $mfa = $this->getRequestedMfa($mfaId);

        if ($mfa->type != Mfa::TYPE_WEBAUTHN) {
            throw new ForbiddenHttpException("May not delete a credential on a $mfa->type mfa type", 1658237110);
        }

        $backend = Mfa::getBackendForType(Mfa::TYPE_WEBAUTHN);

        if ($backend->delete($mfa->id, $webauthnId) === false) {
            \Yii::error([
                'action' => 'delete mfa credential',
                'status' => 'error',
                'error' => $mfa->getFirstErrors(),
                'mfa_id' => $mfa->id,
            ]);
            throw new ServerErrorHttpException("Unable to delete MFA credential", 1658239000);
        }

        \Yii::$app->response->statusCode = 204;
        return null;
    }

    /**
     * @param int $id
     * @return Mfa|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionUpdate(int $id)
    {
        $mfa = $this->getRequestedMfa($id);

        $label = \Yii::$app->request->getBodyParam('label');
        if ($label === null) {
            return $mfa;
        }

        $mfa->setLabel($label);

        if ($mfa->update() === false) {
            \Yii::error([
                'action' => 'update mfa',
                'status' => 'error',
                'error' => $mfa->getFirstErrors(),
                'mfa_id' => $mfa->id,
            ]);
            throw new ServerErrorHttpException("Unable to update MFA option", 1543873675);
        }

        return $mfa;
    }


    /**
     * @param int $mfaId
     * @param int $webauthnId
     * @return MfaWebauthn
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionUpdateWebauthn(int $mfaId, int $webauthnId)
    {
        // validate that the parent MFA records exists and has the Employee ID provided in the request
        $this->getRequestedMfa($mfaId);

        $webauthn = MfaWebauthn::findOne(['id' => $webauthnId, 'mfa_id' => $mfaId]);
        if ($webauthn === null) {
            throw new NotFoundHttpException(
                "MfaWebauthn not found with id: $webauthnId and mfa_id: $mfaId",
                1660232933
            );
        }

        $label = \Yii::$app->request->getBodyParam('label');
        $webauthn->label = $label ?: "";

        if ($webauthn->validate() === false) {
            \Yii::error([
                'action' => 'update mfa webauthn',
                'status' => 'error',
                'error' => $webauthn->getFirstErrors(),
                'mfa_id' => $webauthn->id,
            ]);
            throw new BadRequestHttpException("Invalid data updating MfaWebauthn label", 1660233470);
        }

        if ($webauthn->update() === false) {
            \Yii::error([
                'action' => 'update mfa webauthn',
                'status' => 'error',
                'error' => $webauthn->getFirstErrors(),
                'mfa_id' => $webauthn->id,
            ]);
            throw new ServerErrorHttpException("Unable to update MfaWebauthn label", 1660233472);
        }

        return $webauthn;
    }
}
