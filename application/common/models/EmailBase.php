<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "email".
 *
 * @property int $id
 * @property string $to_address
 * @property string|null $cc_address
 * @property string|null $bcc_address
 * @property string $subject
 * @property string|null $text_body
 * @property string|null $html_body
 * @property int $attempts_count
 * @property int|null $updated_at
 * @property int $created_at
 * @property int|null $send_after
 */
class EmailBase extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'email';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['to_address', 'subject', 'created_at'], 'required'],
            [['text_body', 'html_body'], 'string'],
            [['attempts_count', 'updated_at', 'created_at', 'send_after'], 'integer'],
            [['to_address', 'cc_address', 'bcc_address', 'subject'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'to_address' => Yii::t('app', 'To Address'),
            'cc_address' => Yii::t('app', 'Cc Address'),
            'bcc_address' => Yii::t('app', 'Bcc Address'),
            'subject' => Yii::t('app', 'Subject'),
            'text_body' => Yii::t('app', 'Text Body'),
            'html_body' => Yii::t('app', 'Html Body'),
            'attempts_count' => Yii::t('app', 'Attempts Count'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'created_at' => Yii::t('app', 'Created At'),
            'send_after' => Yii::t('app', 'Send After'),
        ];
    }
}
