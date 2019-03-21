<?php
use yii\helpers\Html as yHtml;

/**
 * @var string $employeeId
 * @var string $firstName
 * @var string $lastName
 * @var string $displayName
 * @var string $username
 * @var string $email
 * @var string $active
 * @var string $locked
 * @var string $lastChangedUtc
 * @var string $lastSyncedUtc
 * @var string $lastLoginUtc
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
?>
<p>Dear <?= yHtml::encode($displayName) ?>,</p>

<p>
    A new <?= yHtml::encode($idpDisplayName) ?> Identity account has been created for you and is ready for you to set
    your password. Please note this account is not an email account. Instead this account is for use with
    corporate applications.
</p>
<p>
    After creating your password you'll also be given the opportunity to set up recovery methods
    in case you ever forget your password. We highly recommended that you set up at least one or two recovery methods.
    You can also enhance the security of your account by enabling 2-Step Verification which will help ensure bad guys
    cannot get into your account even if they guess your password.
</p>

<p>
    If you have any difficulties completing this task, please contact
    <?= yHtml::encode($supportName) ?> at: <?= yHtml::encode($supportEmail) ?>
</p>

<p><b>Instructions:</b></p>
<p>
    To proceed with establishing your account, please follow the step-by-step guidance
    <?= yHtml::a('here', $passwordProfileUrl . '/profile/intro?invite=' . $inviteCode) ?>.
</p>

<p>
    Please note that this <?= yHtml::encode($idpDisplayName) ?> Identity account (username and password) is
    unique and its password is not synchronized with any other accounts you may have.
</p>

<p>Thank you,</p>

<p><i><?= yHtml::encode($emailSignature) ?></i></p>
