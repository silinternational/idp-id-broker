<?php
namespace frontend\controllers;

use common\helpers\Utils;
use common\models\User;
use frontend\components\BaseRestController;
use yii\web\UnprocessableEntityHttpException;

class UserController extends BaseRestController
{
    /**
     * Create a new User.  If User already exists, updates will occur but only for the
     * fields that would've been accepted during a normal creation.
     */
    public function actionCreate()
    {
        $existingUser = User::findOne([
            'employee_id' => \Yii::$app->request->getBodyParam('employee_id')
        ]);

        $user = $existingUser ?? new User(['scenario' => User::SCENARIO_CREATE]);

        $user->attributes = \Yii::$app->request->getBodyParams();

        if ($this->hasQualifyingChanges($user)) {
            $user->last_changed_utc = gmdate(Utils::DT_FMT);
        }

        $user->last_synced_utc = gmdate(Utils::DT_FMT);

        if (! $user->save()) {
            throw new UnprocessableEntityHttpException(current($user->getFirstErrors()));
        }

        return $user;
    }

    /**
     * Return list of users
     */
    public function actionIndex()
    {
        //TODO: needs to support query string parms for search by certain field, e.g.,
        // GET /user?username="kitten_lover" returns all user records matching on username.
    }

    /**
     * Return the User associated with a specific employee id.
     */
    public function actionView($employeeId)
    {
        // verify employee id (if necessary)
        // perform lookup
        // return User
    }

    /**
     * Updates the User associated with a specific employee id.
     *
     */
    public function actionUpdate($employeeId)
    {
    }

    private function hasQualifyingChanges($user): bool
    {
        return !empty($user->getDirtyAttributes());
    }
}
