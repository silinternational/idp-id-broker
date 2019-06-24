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
 * @var string $inviteCode
 */

$inviteLink = $passwordProfileUrl . '/profile/intro?invite=' . $inviteCode;

?>
<p>Dear <?= yHtml::encode($displayName) ?>,</p>

<p>
    A new <?= yHtml::encode($idpDisplayName) ?> Identity account has been created for you and is ready for you to set
    your password. Please note this account is not an email account. Instead this account is for use with
    corporate applications.
</p>

<p>
    If you have any difficulties completing this task, please contact
    <?= yHtml::encode($supportName) ?> at: <?= yHtml::encode($supportEmail) ?>
</p>

<p><b>Instructions:</b></p>
<p>
    To set up your account, please follow the step-by-step guidance
    here: <?= yHtml::a(yHtml::encode($inviteLink), $inviteLink) ?>
</p>

<p>
    Please note that this <?= yHtml::encode($idpDisplayName) ?> Identity account (username and password) is
    unique and its password is not synchronized with any other accounts you may have.
</p>

<p>Thank you,</p>

<p><i><?= nl2br(yHtml::encode($emailSignature), false) ?></i></p>
