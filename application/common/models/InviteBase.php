<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "invite".
 *
 * @property int $id
 * @property int $user_id
 * @property string $uuid
 * @property string $created_utc
 * @property string $expires_on
 *
 * @property User $user
 */
class InviteBase extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'invite';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'uuid', 'created_utc', 'expires_on'], 'required'],
            [['user_id'], 'integer'],
            [['created_utc', 'expires_on'], 'safe'],
            [['uuid'], 'string', 'max' => 36],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'uuid' => Yii::t('app', 'Uuid'),
            'created_utc' => Yii::t('app', 'Created Utc'),
            'expires_on' => Yii::t('app', 'Expires On'),
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
