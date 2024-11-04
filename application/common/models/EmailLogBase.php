<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "email_log".
 *
 * @property int $id
 * @property string|null $message_type
 * @property string $sent_utc
 * @property string $to_address
 */
class EmailLogBase extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'email_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['message_type'], 'string'],
            [['sent_utc'], 'required'],
            [['sent_utc'], 'safe'],
            [['to_address'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'message_type' => Yii::t('app', 'Message Type'),
            'sent_utc' => Yii::t('app', 'Sent Utc'),
            'to_address' => Yii::t('app', 'To Address'),
        ];
    }
}
