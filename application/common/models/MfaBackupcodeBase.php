<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mfa_backupcode".
 *
 * @property int $id
 * @property int $mfa_id
 * @property string $value
 * @property string $created_utc
 * @property string|null $expires_utc
 *
 * @property Mfa $mfa
 */
class MfaBackupcodeBase extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mfa_backupcode';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mfa_id', 'value', 'created_utc'], 'required'],
            [['mfa_id'], 'integer'],
            [['created_utc', 'expires_utc'], 'safe'],
            [['value'], 'string', 'max' => 255],
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
            'value' => Yii::t('app', 'Value'),
            'created_utc' => Yii::t('app', 'Created Utc'),
            'expires_utc' => Yii::t('app', 'Expires Utc'),
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
