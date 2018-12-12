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

        $authentication = new Authentication(
            (string)Yii::$app->request->getBodyParam('username'),
            (string)Yii::$app->request->getBodyParam('password'),
            $migratePasswords ? Yii::$app->ldap : null,
            (string)Yii::$app->request->getBodyParam('invite')
        );

        $authenticatedUser = $authentication->getAuthenticatedUser();

        if ($authenticatedUser !== null) {
            return $authenticatedUser;
        }

        \Yii::error([
            'action' => 'authentication',
            'status' => 'error',
            'message' => $authentication->getErrors(),
        ]);
        throw new BadRequestHttpException();
    }
}
