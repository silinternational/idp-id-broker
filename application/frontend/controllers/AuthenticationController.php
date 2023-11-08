<?php
namespace frontend\controllers;

use common\models\Authentication;
use common\models\User;
use frontend\components\BaseRestController;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

class AuthenticationController extends BaseRestController
{
    /**
     * Authenticates a user based on his/her password or invite code
     *
     * @return User
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionCreate(): User
    {
        $username = (string)Yii::$app->request->getBodyParam('username');
        $password = (string)Yii::$app->request->getBodyParam('password');
        $inviteCode = (string)Yii::$app->request->getBodyParam('invite');

        // rpOrigin is needed for WebAuthn authentication
        $rpOrigin = \Yii::$app->request->get('rpOrigin', '');
        if ($rpOrigin != '' && !in_array($rpOrigin, \Yii::$app->params['authorizedRPOrigins'])){
            throw new ForbiddenHttpException("Invalid rpOrigin", 1639169238);
        }

        $authentication = new Authentication(
            $username,
            $password,
            $inviteCode
        );

        $authenticatedUser = $authentication->getAuthenticatedUser();

        /**
         * This code will benchmark your server to determine how high of a cost you can
         * afford. You want to set the highest cost that you can without slowing down
         * you server too much. 10 is a good baseline, and more is good if your servers
         * are fast enough. The code below aims for ≤ 350 milliseconds stretching time,
         * which is an appropriate delay for systems handling interactive logins.
         */
        $timeTarget = 0.350; // 350 milliseconds

        $cost = 10;
        do {
            $cost++;
            $start = microtime(true);
            password_hash("test", PASSWORD_BCRYPT, ["cost" => $cost]);
            $end = microtime(true);
        } while (($end - $start) < $timeTarget);

        $log = [
            'action' => 'authentication/create',
            'username' => $username,
            'password' => empty($password) ? 'no' : 'yes',
            'invite' => empty($inviteCode) ? 'no' : 'yes',
            'cost' => $cost,
        ];

        if ($authenticatedUser !== null) {
            $log['status'] = 'created';
            Yii::info($log, 'application');
            $authenticatedUser->loadMfaData($rpOrigin);
            return $authenticatedUser;
        }

        $log['status'] = 'failed';
        $log['errors'] = $authentication->getErrors();
        Yii::warning($log, 'application');

        throw new BadRequestHttpException();
    }
}
