<?php

namespace common\components;

use Sil\JsonLog\JsonLogHelper;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\log\Target;

/*
 * EmailLogTarget is a Yii2 log target for alerting by email
 *
 * Derived from EmailServiceTarget in silinternational/yii2-json-log-targets
 */
class EmailLogTarget extends Target
{
    /**
     * @var array $message Email config, properties: to, cc, bcc, subject
     */
    public $message;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (empty($this->message['to'])) {
            throw new InvalidConfigException('The "to" option must be set for EmailLogTarget::message.');
        }

        $this->message['subject'] = $this->message['subject'] ?? 'System Alert';
        $this->message['cc'] = $this->message['cc'] ?? '';
        $this->message['bcc'] = $this->message['bcc'] ?? '';
    }


    /**
     * Format a log message as a string of JSON.
     *
     * @param array $logMessageData The array of log data provided by Yii. See
     *     `\yii\log\Logger::messages`.
     * @return string The JSON-encoded log data.
     */
    public function formatMessage($logMessageData)
    {
        $jsonString = JsonLogHelper::formatAsJson(
            $logMessageData,
            $this->getMessagePrefix($logMessageData)
        );

        return Json::encode(Json::decode($jsonString), JSON_PRETTY_PRINT);
    }

    /**
     * Send message to Email Service
     */
    public function export()
    {
        $emailClient = new EmailClient();

        foreach ($this->messages as $msg) {
            $body = $this->formatMessage($msg);

            $emailClient->email([
                'to_address' => $this->message['to'],
                'cc_address' => $this->message['cc'],
                'bcc_address' => $this->message['bcc'],
                'subject' => $this->message['subject'],
                'text_body' => $body,
                'html_body' => sprintf("<pre>%s</pre>", $body),
            ]);
        }
    }
}
