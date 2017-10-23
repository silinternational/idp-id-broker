<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mfa".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $type
 * @property string $external_uuid
 * @property string $label
 * @property integer $verified
 * @property string $created_utc
 * @property string $last_used_utc
 *
 * @property User $user
 * @property MfaBackupcode[] $mfaBackupcodes
 */
class MfaBase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mfa';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'type', 'verified', 'created_utc'], 'required'],
            [['user_id', 'verified'], 'integer'],
            [['type'], 'string'],
            [['created_utc', 'last_used_utc'], 'safe'],
            [['external_uuid', 'label'], 'string', 'max' => 64],
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
            'type' => Yii::t('app', 'Type'),
            'external_uuid' => Yii::t('app', 'External Uuid'),
            'label' => Yii::t('app', 'Label'),
            'verified' => Yii::t('app', 'Verified'),
            'created_utc' => Yii::t('app', 'Created Utc'),
            'last_used_utc' => Yii::t('app', 'Last Used Utc'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMfaBackupcodes()
    {
        return $this->hasMany(MfaBackupcode::className(), ['mfa_id' => 'id']);
    }
}
