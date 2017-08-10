<?php
namespace common\components;

use common\models\EmailLog;
use common\models\User;
use Sil\EmailService\Client\EmailServiceClient;
use yii\base\Component;
use yii\web\ServerErrorHttpException;

class Emailer extends Component
{
    /**
     * The configuration, primarily for the email-service client.
     *
     * @var array
     */
    public $config = [];
    
    /** @var EmailServiceClient */
    private $emailServiceClient = null;
    
    public $sendInviteEmails = false;
    public $sendWelcomeEmails = false;
    
    /**
     * Use the email service to send an email.
     *
     * @param string $toAddress The recipient's email address.
     * @param string $subject The subject.
     * @param string $htmlBody The email body (as an HTML string).
     */
    public function email(string $toAddress, string $subject, string $htmlBody)
    {
        $this->getEmailServiceClient()->email([
            'to_address' => $toAddress,
            'subject' => $subject,
            'html_body' => $htmlBody,
        ]);
    }
    
    /**
     * @return EmailServiceClient
     */
    protected function getEmailServiceClient()
    {
        if ($this->emailServiceClient === null) {
            
            $this->emailServiceClient = new EmailServiceClient(
                $this->config['baseUrl'],
                $this->config['accessToken'],
                [
                    EmailServiceClient::ASSERT_VALID_IP_CONFIG => $this->config['assertValidIp'],
                    EmailServiceClient::TRUSTED_IPS_CONFIG => $this->config['validIpRanges'],
                ]
            );
        }
        
        return $this->emailServiceClient;
    }
    
    /**
     * Ensure that we have the required configuration data.
     *
     * @throws ServerErrorHttpException
     */
    public function init()
    {
        $requiredParams = [
            'accessToken',
            'assertValidIp',
            'baseUrl',
            'validIpRanges',
        ];
        
        foreach ($requiredParams as $param) {
            if ( ! isset($this->config[$param])) {
                throw new ServerErrorHttpException(
                    'Missing email service configuration for ' . $param,
                    1502311757
                );
            }
        }
        
        parent::init();
    }
    
    /**
     * Whether we should send an invite message to the given User.
     *
     * @param User $user The User in question.
     * @param bool $isNewUser Whether the User record was just created (insert,
     *     not update).
     * @return bool
     */
    public function shouldSendInviteMessageTo($user, $isNewUser)
    {
        return $this->sendInviteEmails
            && $isNewUser
            && !$user->hasReceivedMessage(EmailLog::MESSAGE_TYPE_INVITE);
    }
    
    /**
     * Whether we should send a welcome message to the given User.
     *
     * @param User $user The User in question.
     * @return bool
     */
    public function shouldSendWelcomeMessageTo($user)
    {
        return $this->sendWelcomeEmails
            && !$user->hasReceivedMessage(EmailLog::MESSAGE_TYPE_WELCOME);
    }
}
