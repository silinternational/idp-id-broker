<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property integer $id
 * @property string $uuid
 * @property string $employee_id
 * @property string $first_name
 * @property string $last_name
 * @property string $display_name
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property string $active
 * @property string $locked
 * @property string $blocked_until_utc
 * @property string $last_changed_utc
 * @property string $last_synced_utc
 *
 * @property PreviousPassword[] $previousPasswords
 */
class UserBase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uuid', 'employee_id', 'first_name', 'last_name', 'username', 'email', 'active', 'locked', 'last_changed_utc', 'last_synced_utc'], 'required'],
            [['active', 'locked'], 'string'],
            [['blocked_until_utc', 'last_changed_utc', 'last_synced_utc'], 'safe'],
            [['uuid'], 'string', 'max' => 64],
            [['employee_id', 'first_name', 'last_name', 'display_name', 'username', 'email', 'password_hash'], 'string', 'max' => 255],
            [['employee_id'], 'unique'],
            [['username'], 'unique'],
            [['email'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'uuid' => Yii::t('app', 'Uuid'),
            'employee_id' => Yii::t('app', 'Employee ID'),
            'first_name' => Yii::t('app', 'First Name'),
            'last_name' => Yii::t('app', 'Last Name'),
            'display_name' => Yii::t('app', 'Display Name'),
            'username' => Yii::t('app', 'Username'),
            'email' => Yii::t('app', 'Email'),
            'password_hash' => Yii::t('app', 'Password Hash'),
            'active' => Yii::t('app', 'Active'),
            'locked' => Yii::t('app', 'Locked'),
            'blocked_until_utc' => Yii::t('app', 'Blocked Until Utc'),
            'last_changed_utc' => Yii::t('app', 'Last Changed Utc'),
            'last_synced_utc' => Yii::t('app', 'Last Synced Utc'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPreviousPasswords()
    {
        return $this->hasMany(PreviousPassword::className(), ['user_id' => 'id']);
    }
}
