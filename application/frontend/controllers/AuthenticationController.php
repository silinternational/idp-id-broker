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
     * @return User upon successful authentication, i.e., "creation".
     * @throws BadRequestHttpException
     */
    public function actionCreate(): User
    {
        $migratePasswords = Yii::$app->params['migratePasswordsFromLdap'];

        $authentication = new Authentication(
            (string)Yii::$app->request->getBodyParam('username'),
            (string)Yii::$app->request->getBodyParam('password'),
            $migratePasswords ? Yii::$app->ldap : null
        );

        $authenticatedUser = $authentication->getAuthenticatedUser();

        if ($authenticatedUser !== null) {
            return $authenticatedUser;
        }

        throw new BadRequestHttpException();
    }
}
