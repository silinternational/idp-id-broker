<?php
namespace frontend\components;

use yii\db\ActiveRecord;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\web\MethodNotAllowedHttpException;
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

    protected function save(ActiveRecord $record)
    {
        if (! $record->save()) {
            throw new UnprocessableEntityHttpException(current($record->getFirstErrors()));
        }
    }

    public function actionCreate()
    {
        $this->throwNotAllowed();
    }

    public function actionIndex()
    {
        $this->throwNotAllowed();
    }

    public function actionUpdate()
    {
        $this->throwNotAllowed();
    }

    public function actionDelete()
    {
        $this->throwNotAllowed();
    }

    public function actionView()
    {
        $this->throwNotAllowed();
    }

    public function actionOptions()
    {
        $this->throwNotAllowed();
    }

    private function throwNotAllowed()
    {
        throw new MethodNotAllowedHttpException('Method not allowed.');
    }
}
