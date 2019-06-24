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
    Congratulations! You have enabled 2-Step Verification on your <?= yHtml::encode($idpDisplayName) ?>
    Identity account. This is a great way to protect access to your account as
    well as to keep bad guys out of our corporate systems.
</p>
<p>
    The next time you log in to a site using your <?= yHtml::encode($idpDisplayName) ?> Identity
    account you will be prompted for 2-Step Verification. On that screen you'll
    see a checkbox already checked to remember your browser for 30 days. If you
    leave it checked, you will only be prompted for 2-Step Verification once a
    month or so.
</p>
<p>
    If you have not already done so, we recommend configuring at least two
    options for 2-Step Verification in case one of the options is not available
    to you when logging in. Having more than one method is not required though,
    just recommended.
</p>
<p>
    Thanks,
</p>
<p><i><?= nl2br(yHtml::encode($emailSignature), false) ?></i></p>
