<?php
use yii\helpers\Html as yHtml;

/**
 * @var string $firstName
 * @var string $displayName
 * @var string $username
 * @var string $email
 * @var string $passwordExpiresUtc
 * @var string $emailSignature
 * @var string $helpCenterUrl
 * @var string $idpDisplayName
 * @var string $passwordProfileUrl
 * @var string $supportEmail
 * @var string $supportName
 * @var bool   $isMfaEnabled
 */
?>
<p>Dear <?= yHtml::encode($displayName) ?>,</p>
<p>
    There have been too many failed 2-Step Verification attempts on your 
    <?= yHtml::encode($idpDisplayName) ?> Identity account. You will need to wait at least 
    5 minutes and try again.
</p>
<p>
    If you are not currently trying to log into your <?= yHtml::encode($idpDisplayName) ?> Identity 
    account, it could be a sign someone else is trying to access your account. 
    Please contact <?= yHtml::encode($supportName) ?> at <?= yHtml::encode($supportEmail) ?> as soon as possible 
    to report the incident.
</p>
<p>
    If you continue to have problems accessing your account, please contact 
    <?= yHtml::encode($supportName) ?> at <?= yHtml::encode($supportEmail) ?>.
</p>
<p>
    Thanks,
</p>
<p>
    <i><?= nl2br(yHtml::encode($emailSignature), false) ?></i>
</p>
