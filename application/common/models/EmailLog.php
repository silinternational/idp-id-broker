<?php
namespace common\models;

use common\helpers\MySqlDateTime;
use Yii;
use yii\helpers\ArrayHelper;

class EmailLog extends EmailLogBase
{
    /* Valid message_type values.
     *
     * NOTE: Changes must be made here, in the `getMessageTypes()` method below,
     *       and in the email_log.message_type enum list in the database.
     */
    const MESSAGE_TYPE_INVITE = 'invite';
    const MESSAGE_TYPE_WELCOME = 'welcome';

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'sent_utc' => Yii::t('app', 'Sent (UTC)'),
        ]);
    }
    
    public static function getMessageTypes()
    {
        return [
            self::MESSAGE_TYPE_INVITE,
            self::MESSAGE_TYPE_WELCOME,
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge([
            [
                'message_type',
                'required',
            ],
            [
                'message_type',
                'in',
                'range' => self::getMessageTypes(),
                'strict' => true,
            ],
            [
                'sent_utc',
                'default',
                'value' => MySqlDateTime::now(),
            ],
        ], parent::rules());
    }
}
