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
    2-Step Verification has been disabled on your <?= yHtml::encode($idpDisplayName) ?> Identity account.
    If this was not intentional, follow the instructions below to setup 2-Step Verification on your account.
</p>
<p>
    If you did not do this, it could be a sign someone else has compromised your account.
    Please contact <?= yHtml::encode($supportName) ?> at <?= yHtml::encode($supportEmail) ?>
    as soon as possible to report the incident.
</p>

<p><b>Instructions to set up 2-Step Verification:</b></p>
<ol>
    <li>Go to <?=yHtml::a(yHtml::encode($passwordProfileUrl), $passwordProfileUrl)?>.</li>
    <li>Under 2-Step Verification, set up the options that suit you best (Security Key, Authenticator App, and/or
    Printable Codes)</li>
    <li>Log out and log in again to see how it works and to have it remember your browser for 30 days.</li>
</ol>
<?php if (!empty($helpCenterUrl)) { ?>
<p>
    To learn more about 2-Step Verification go to <?=yHtml::a(yHtml::encode($helpCenterUrl), $helpCenterUrl)?>
</p>
<?php } ?>
<p>
    If you have any difficulties completing this task, please contact <?= yHtml::encode($supportName) ?> at
<?= yHtml::encode($supportEmail) ?>.
</p>

<p>
    Thanks,
</p>
<p><i><?= nl2br(yHtml::encode($emailSignature), false) ?></i></p>
