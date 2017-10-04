<?php
namespace frontend\controllers;

use common\models\Authentication;
use common\models\User;
use frontend\components\BaseRestController;
use Yii;
use yii\web\BadRequestHttpException;

class AuthenticationController extends BaseRestController
{
    /**
     * Authenticates the given user based on his/her password
     *
     * @return User|array Will return User object if MFA is not required, or array of MFA details if it is
     * @throws BadRequestHttpException
     */
    public function actionCreate()
    {
        $migratePasswords = Yii::$app->params['migratePasswordsFromLdap'];

        $authentication = new Authentication(
            (string)Yii::$app->request->getBodyParam('username'),
            (string)Yii::$app->request->getBodyParam('password'),
            $migratePasswords ? Yii::$app->ldap : null
        );

        $authenticatedUser = $authentication->getAuthenticatedUser();

        if ($authenticatedUser !== null) {
            $mfaArray = $authenticatedUser->toArray([
                'employee_id',
                'prompt_for_mfa',
                'mfa_options',
            ]);
            if ($mfaArray['prompt_for_mfa'] == 'yes') {
                return $mfaArray;
            }

            return $authenticatedUser;
        }

        throw new BadRequestHttpException();
    }
}
