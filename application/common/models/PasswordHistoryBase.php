<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "password_history".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $password_hash
 * @property string $created_utc
 *
 * @property User $user
 */
class PasswordHistoryBase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'password_history';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'password_hash', 'created_utc'], 'required'],
            [['user_id'], 'integer'],
            [['created_utc'], 'safe'],
            [['password_hash'], 'string', 'max' => 255],
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
            'password_hash' => Yii::t('app', 'Password Hash'),
            'created_utc' => Yii::t('app', 'Created Utc'),
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
