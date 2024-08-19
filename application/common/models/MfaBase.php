<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mfa".
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string|null $external_uuid
 * @property string|null $label
 * @property int $verified
 * @property string $created_utc
 * @property string|null $last_used_utc
 * @property string|null $key_handle_hash
 *
 * @property MfaBackupcode[] $mfaBackupcodes
 * @property MfaFailedAttempt[] $mfaFailedAttempts
 * @property MfaWebauthn[] $mfaWebauthns
 * @property User $user
 */
class MfaBase extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mfa';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'type', 'verified', 'created_utc'], 'required'],
            [['user_id', 'verified'], 'integer'],
            [['type'], 'string'],
            [['created_utc', 'last_used_utc'], 'safe'],
            [['external_uuid', 'label'], 'string', 'max' => 64],
            [['key_handle_hash'], 'string', 'max' => 255],
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
            'type' => Yii::t('app', 'Type'),
            'external_uuid' => Yii::t('app', 'External Uuid'),
            'label' => Yii::t('app', 'Label'),
            'verified' => Yii::t('app', 'Verified'),
            'created_utc' => Yii::t('app', 'Created Utc'),
            'last_used_utc' => Yii::t('app', 'Last Used Utc'),
            'key_handle_hash' => Yii::t('app', 'Key Handle Hash'),
        ];
    }

    /**
     * Gets query for [[MfaBackupcodes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMfaBackupcodes()
    {
        return $this->hasMany(MfaBackupcode::class, ['mfa_id' => 'id']);
    }

    /**
     * Gets query for [[MfaFailedAttempts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMfaFailedAttempts()
    {
        return $this->hasMany(MfaFailedAttempt::class, ['mfa_id' => 'id']);
    }

    /**
     * Gets query for [[MfaWebauthns]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMfaWebauthns()
    {
        return $this->hasMany(MfaWebauthn::class, ['mfa_id' => 'id']);
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
