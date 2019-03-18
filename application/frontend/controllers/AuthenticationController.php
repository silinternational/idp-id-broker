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
     * Authenticates a user based on his/her password or invite code
     *
     * @return User
     * @throws BadRequestHttpException
     */
    public function actionCreate(): User
    {
        $migratePasswords = Yii::$app->params['migratePasswordsFromLdap'];

        $username = (string)Yii::$app->request->getBodyParam('username');
        $password = (string)Yii::$app->request->getBodyParam('password');
        $inviteCode = (string)Yii::$app->request->getBodyParam('invite');

        $authentication = new Authentication(
            $username,
            $password,
            $migratePasswords ? Yii::$app->ldap : null,
            $inviteCode
        );

        $authenticatedUser = $authentication->getAuthenticatedUser();

        $log = [
            'action' => 'authentication/create',
            'username' => $username,
            'password' => empty($password) ? 'no' : 'yes',
            'invite' => empty($inviteCode) ? 'no' : 'yes',
        ];

        if ($authenticatedUser !== null) {
            $log['status'] = 'created';
            Yii::info($log, 'application');

            return $authenticatedUser;
        }

        $log['status'] = 'failed';
        $log['errors'] = $authentication->getErrors();
        Yii::warning($log, 'application');

        throw new BadRequestHttpException();
    }
}
