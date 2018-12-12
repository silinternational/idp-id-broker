<?php

namespace common\models;

use Closure;
use common\helpers\MySqlDateTime;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\helpers\ArrayHelper;

class Invite extends InviteBase
{
    public function rules(): array
    {
        return ArrayHelper::merge([
            [
                'uuid', 'default', 'value' => Uuid::uuid4()->toString()
            ],
            [
                'created_utc', 'default', 'value' => MySqlDateTime::now(),
            ],
            [
                'expires_on', 'default', 'value' => $this->expires(),
            ],
        ], parent::rules());
    }

    private function expires(): Closure
    {
        return function() {
            $lifespan = Yii::$app->params['inviteLifespan'];

            return MySqlDateTime::formatDate(strtotime($lifespan, strtotime($this->created_utc)));
        };
    }

    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();

        $labels['uuid'] = Yii::t('app', 'UUID');
        $labels['created_utc'] = Yii::t('app', 'Created (UTC)');

        return $labels;
    }

    public function isValidCode()
    {
        $expiration = strtotime($this->expires_on);

        $now = time();
        if ($now > $expiration) {
            return false;
        }
        return true;
    }

    public static function getInviteCode(int $userId): string
    {
        /* @var $invite Invite */
        $invite = self::find()
            ->where(['user_id' => $userId])
            ->andWhere(['>', 'expires_on', MySqlDateTime::today()])
            ->one();

        try {
            if ($invite === null) {
                $invite = new Invite();
                $invite->user_id = $userId;
                $invite->save();
            }
        } catch (\Throwable $t) {
            \Yii::error([
                'action' => 'create invite code',
                'status' => 'error',
                'userId' => $userId,
                'message' => $t->getMessage(),
            ]);
            return '';
        }

        return $invite->uuid;
    }
}
