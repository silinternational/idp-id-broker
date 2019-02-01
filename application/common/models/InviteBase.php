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
            [['user_id'], 'integer'],
            [['uuid', 'created_utc', 'expires_on'], 'required'],
            [['created_utc', 'expires_on'], 'safe'],
            [['uuid'], 'string', 'max' => 36],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
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
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
