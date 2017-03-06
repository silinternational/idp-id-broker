<?php
namespace frontend\components;

use yii\db\ActiveRecord;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\web\UnprocessableEntityHttpException;

class BaseRestController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Use request header-> 'Authorization: Bearer <token>'
        $behaviors['authenticator']['class'] = HttpBearerAuth::className();

        return $behaviors;
    }

    protected function save(ActiveRecord ...$records)
    {
        $transaction = ActiveRecord::getDb()->beginTransaction();

        try {
            foreach ($records as $record) {
                if (! $record->save()) {
                    throw new UnprocessableEntityHttpException(current($record->getFirstErrors()));
                }
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }
    }
}
