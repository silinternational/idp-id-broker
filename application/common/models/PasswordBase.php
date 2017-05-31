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
 * @property string $expiration_utc
 * @property string $grace_period_ends_utc
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
            [['user_id', 'hash', 'created_utc', 'expiration_utc', 'grace_period_ends_utc'], 'required'],
            [['user_id'], 'integer'],
            [['created_utc', 'expiration_utc', 'grace_period_ends_utc'], 'safe'],
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
            'expiration_utc' => Yii::t('app', 'Expiration Utc'),
            'grace_period_ends_utc' => Yii::t('app', 'Grace Period Ends Utc'),
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
