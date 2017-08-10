<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "email_log".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $to_address
 * @property string $message_type
 * @property string $sent_utc
 *
 * @property User $user
 */
class EmailLogBase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'email_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'to_address', 'sent_utc'], 'required'],
            [['user_id'], 'integer'],
            [['message_type'], 'string'],
            [['sent_utc'], 'safe'],
            [['to_address'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'to_address' => Yii::t('app', 'To Address'),
            'message_type' => Yii::t('app', 'Message Type'),
            'sent_utc' => Yii::t('app', 'Sent Utc'),
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
