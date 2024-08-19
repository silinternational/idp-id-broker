<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mfa_webauthn".
 *
 * @property int $id
 * @property int $mfa_id
 * @property string|null $key_handle_hash
 * @property string $label
 * @property string $created_utc
 * @property string|null $last_used_utc
 *
 * @property Mfa $mfa
 */
class MfaWebauthnBase extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mfa_webauthn';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mfa_id', 'label', 'created_utc'], 'required'],
            [['mfa_id'], 'integer'],
            [['created_utc', 'last_used_utc'], 'safe'],
            [['key_handle_hash'], 'string', 'max' => 255],
            [['label'], 'string', 'max' => 64],
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
            'key_handle_hash' => Yii::t('app', 'Key Handle Hash'),
            'label' => Yii::t('app', 'Label'),
            'created_utc' => Yii::t('app', 'Created Utc'),
            'last_used_utc' => Yii::t('app', 'Last Used Utc'),
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
