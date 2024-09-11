<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mfa_failed_attempt".
 *
 * @property int $id
 * @property int $mfa_id
 * @property string $at_utc
 *
 * @property Mfa $mfa
 */
class MfaFailedAttemptBase extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mfa_failed_attempt';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mfa_id', 'at_utc'], 'required'],
            [['mfa_id'], 'integer'],
            [['at_utc'], 'safe'],
            [['mfa_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mfa::class, 'targetAttribute' => ['mfa_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'mfa_id' => Yii::t('app', 'Mfa ID'),
            'at_utc' => Yii::t('app', 'At Utc'),
        ];
    }

    /**
     * Gets query for [[Mfa]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMfa()
    {
        return $this->hasOne(Mfa::class, ['id' => 'mfa_id']);
    }
}
