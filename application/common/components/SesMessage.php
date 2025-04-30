<?php

namespace common\components;

use yii\base\NotSupportedException;
use yii\mail\BaseMessage;

/*
 * SesMessage is a Yii2 message component to send email messages using AWS Simple Email Service
 *
 * Copied from silinternational/email-service
 */
class SesMessage extends BaseMessage
{
    /** @var string */
    private $charset;

    /** @var string */
    private $from;

    /** @var string[] */
    private $to;

    /** @var string[] */
    private $replyTo;

    /** @var string[] */
    private $cc;

    /** @var string[] */
    private $bcc;

    /** @var string */
    private $subject;

    /** @var string */
    private $textBody;

    /** @var string */
    private $htmlBody;

    public function getCharset()
    {
        return $this->charset ?? 'UTF-8';
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function setFrom($from)
    {
        if (is_array($from)) {
            if (isset($from[0])) {
                $this->from = $from[0];
            } else {
                $addresses = array_keys($from);
                $names = array_values($from);
                $this->from = sprintf('%s <%s>', $names[0], $addresses[0]);
            }
        } else {
            $this->from = $from;
        }
    }

    public function getTo()
    {
        return $this->to;
    }

    public function setTo($to)
    {
        if (is_array($to)) {
            $this->to = $to;
        } else {
            $this->to = explode(",", $to);
        }
    }

    public function getReplyTo()
    {
        return $this->replyTo ?? [$this->getFrom()];
    }

    public function setReplyTo($replyTo)
    {
        if (is_array($replyTo)) {
            $this->replyTo = $replyTo;
        } else {
            $this->replyTo = explode(",", $replyTo);
        }
    }

    public function getCc()
    {
        return $this->cc ?? [];
    }

    public function setCc($cc)
    {
        if (is_array($cc)) {
            $this->cc = $cc;
        } else {
            $this->cc = explode(",", $cc);
        }
    }

    public function getBcc()
    {
        return $this->bcc ?? [];
    }

    public function setBcc($bcc)
    {
        if (is_array($bcc)) {
            $this->bcc = $bcc;
        } else {
            $this->bcc = explode(",", $bcc);
        }
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getTextBody()
    {
        if (empty($this->textBody)) {
            return strip_tags($this->htmlBody);
        }
        return $this->textBody;
    }

    public function setTextBody($text)
    {
        $this->textBody = $text;
    }

    /**
     * @return string
     */
    public function getHtmlBody()
    {
        if (empty($this->htmlBody)) {
            return htmlspecialchars($this->textBody);
        }
        return $this->htmlBody;
    }

    public function setHtmlBody($html)
    {
        $this->htmlBody = $html;
    }

    public function attach($fileName, array $options = [])
    {
        throw new NotSupportedException('attach is not implemented');
    }

    public function attachContent($content, array $options = [])
    {
        throw new NotSupportedException('attacheContent is not implemented');
    }

    public function embed($fileName, array $options = [])
    {
        throw new NotSupportedException('embed is not implemented');
    }

    public function embedContent($content, array $options = [])
    {
        throw new NotSupportedException('embedContent is not implemented');
    }

    public function toString()
    {
        return $this->textBody;
    }
}
