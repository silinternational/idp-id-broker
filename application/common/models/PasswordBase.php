<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "password".
 *
 * @property int $id
 * @property int $user_id
 * @property string $hash
 * @property string $created_utc
 * @property string $expires_on
 * @property string $grace_period_ends_on
 * @property string $check_hibp_after
 * @property string $hibp_is_pwned
 *
 * @property User[] $users
 */
class PasswordBase extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'password';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'hash', 'created_utc', 'expires_on', 'grace_period_ends_on'], 'required'],
            [['user_id'], 'integer'],
            [['created_utc', 'expires_on', 'grace_period_ends_on', 'check_hibp_after'], 'safe'],
            [['hibp_is_pwned'], 'string'],
            [['hash'], 'string', 'max' => 255],
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
            'hash' => Yii::t('app', 'Hash'),
            'created_utc' => Yii::t('app', 'Created Utc'),
            'expires_on' => Yii::t('app', 'Expires On'),
            'grace_period_ends_on' => Yii::t('app', 'Grace Period Ends On'),
            'check_hibp_after' => Yii::t('app', 'Check Hibp After'),
            'hibp_is_pwned' => Yii::t('app', 'Hibp Is Pwned'),
        ];
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::class, ['current_password_id' => 'id']);
    }
}
