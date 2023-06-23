<?php
namespace frontend\controllers;

use common\models\Authentication;
use common\models\User;
use frontend\components\BaseRestController;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\ServerErrorHttpException;

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

        $log = [
            'action' => 'authentication/create',
            'username' => $username,
            'password' => empty($password) ? 'no' : 'yes',
            'invite' => empty($inviteCode) ? 'no' : 'yes',
        ];

        if ($authenticatedUser !== null) {
            $log['status'] = 'created';
            Yii::info($log, 'application');

            try {
                $authenticatedUser->loadMfaData($rpOrigin);
            } catch (\TypeError $e) {

                $log = [
                    'action' => 'authentication/create',
                    'message' => $e->getMessage(),
                    'backTrace' => $e->getTrace(),
                ];
                Yii::error($log, 'application');
                throw new ServerErrorHttpException("Error calling loadMfaData in Authentication Controller", 1687550025);
            } catch (\Exception $e) {
                throw $e;
            }
            return $authenticatedUser;
        }

        $log['status'] = 'failed';
        $log['errors'] = $authentication->getErrors();
        Yii::warning($log, 'application');

        throw new BadRequestHttpException();
    }
}
