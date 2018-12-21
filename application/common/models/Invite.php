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
                'uuid', 'default', 'value' => self::newCode(),
            ],
            [
                'created_utc', 'default', 'value' => MySqlDateTime::now(),
            ],
            [
                'expires_on', 'default', 'value' => self::newExpireDate(),
            ],
        ], parent::rules());
    }

    private static function newExpireDate()
    {
        $lifespan = Yii::$app->params['inviteLifespan'];

        return MySqlDateTime::relative($lifespan);
    }

    private static function newCode()
    {
        return Uuid::uuid4()->toString();
    }

    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();

        $labels['uuid'] = Yii::t('app', 'UUID');
        $labels['created_utc'] = Yii::t('app', 'Created (UTC)');

        return $labels;
    }

    /**
     * Checks expiration date. If `expires_on` is today or before, `isValidCode`
     * returns `false` and adds an error message.
     *
     * @return bool
     * @throws \Exception
     */
    public function isValidCode()
    {
        $expiration = strtotime($this->expires_on);
        if ($expiration === false) {
            throw new \Exception('Unable to parse expires_on');
        }

        $now = time();
        if ($now > $expiration) {
            $this->addError('expires_on', 'Expired code.');
            return false;
        }
        return true;
    }

    public function getCode(): string
    {
        return $this->uuid;
    }

    /**
     * Return an existing, non-expired invite instance, or create a new instance
     * if no non-expired instances exist.
     *
     * @param int $userId
     * @return Invite
     * @throws \Exception
     */
    public static function findOrCreate(int $userId): Invite
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
                $invite->user->refresh();
            }
        } catch (\Throwable $t) {
            \Yii::error([
                'action' => 'create invite code',
                'status' => 'error',
                'userId' => $userId,
                'message' => $t->getMessage(),
            ]);
            throw new \Exception('Error creating new user invite');
        }

        return $invite;
    }

    /**
     * Create a new code and extend expiration.
     * @return string
     */
    public function renew(): string
    {
        $this->uuid = self::newCode();
        $this->expires_on = self::newExpireDate();
        $this->save();

        return $this->getCode();
    }
}
