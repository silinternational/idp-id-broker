<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "password".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $hash
 * @property string $created_utc
 * @property string $expires_on
 * @property string $grace_period_ends_on
 *
 * @property User[] $users
 */
class PasswordBase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'password';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'hash', 'created_utc', 'expires_on', 'grace_period_ends_on'], 'required'],
            [['user_id'], 'integer'],
            [['created_utc', 'expires_on', 'grace_period_ends_on'], 'safe'],
            [['hash'], 'string', 'max' => 255],
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
            'hash' => Yii::t('app', 'Hash'),
            'created_utc' => Yii::t('app', 'Created Utc'),
            'expires_on' => Yii::t('app', 'Expires On'),
            'grace_period_ends_on' => Yii::t('app', 'Grace Period Ends On'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::className(), ['current_password_id' => 'id']);
    }
}
