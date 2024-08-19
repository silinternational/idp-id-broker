<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "method".
 *
 * @property int $id
 * @property string $uid
 * @property int $user_id
 * @property string $value
 * @property int $verified
 * @property string|null $verification_code
 * @property int|null $verification_attempts
 * @property string|null $verification_expires
 * @property string $created
 *
 * @property User $user
 */
class MethodBase extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'method';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'user_id', 'value', 'created'], 'required'],
            [['user_id', 'verified', 'verification_attempts'], 'integer'],
            [['verification_expires', 'created'], 'safe'],
            [['uid'], 'string', 'max' => 32],
            [['value'], 'string', 'max' => 255],
            [['verification_code'], 'string', 'max' => 64],
            [['uid'], 'unique'],
            [['user_id', 'value'], 'unique', 'targetAttribute' => ['user_id', 'value']],
            [['verification_code'], 'unique'],
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
            'uid' => Yii::t('app', 'Uid'),
            'user_id' => Yii::t('app', 'User ID'),
            'value' => Yii::t('app', 'Value'),
            'verified' => Yii::t('app', 'Verified'),
            'verification_code' => Yii::t('app', 'Verification Code'),
            'verification_attempts' => Yii::t('app', 'Verification Attempts'),
            'verification_expires' => Yii::t('app', 'Verification Expires'),
            'created' => Yii::t('app', 'Created'),
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
