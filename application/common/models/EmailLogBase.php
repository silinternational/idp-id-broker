<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "email_log".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $message_type
 * @property string $sent_utc
 * @property string|null $non_user_address
 *
 * @property User $user
 */
class EmailLogBase extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'email_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['message_type'], 'string'],
            [['sent_utc'], 'required'],
            [['sent_utc'], 'safe'],
            [['non_user_address'], 'string', 'max' => 255],
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
            'message_type' => Yii::t('app', 'Message Type'),
            'sent_utc' => Yii::t('app', 'Sent Utc'),
            'non_user_address' => Yii::t('app', 'Non User Address'),
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
