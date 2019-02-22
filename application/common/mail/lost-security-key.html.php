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
 * @var string $passwordForgotUrl
 * @var string $passwordProfileUrl
 * @var string $supportEmail
 * @var string $supportName
 * @var bool   $isMfaEnabled
 */
?>
<p>Dear <?= yHtml::encode($displayName) ?>,</p>

<p>
    We noticed you have a Security Key configured for 2-Step Verification on your <?= yHtml::encode($idpDisplayName) ?>
    Identity account but have not used it recently and instead have been using a different option for 2-Step Verification
    (a Smartphone App or Printable Codes).
</p>
<p>
    This email is just a courtesy to check if you have lost your Security Key and to remind you to remove it from your
    account to ensure it cannot be used by someone else. If you still have your Security Key and have just been using
    other methods recently you can ignore and delete this email.
</p>
<p>
    If you need to remove the Security Key from your Identity account:
</p>
<ol>
    <li>Go to <?=yHtml::a(yHtml::encode($passwordProfileUrl), $passwordProfileUrl)?>.</li>
    <li>Log in if needed</li>
    <li>Under the 2-Step Verification section, click "I LOST MY KEY" next to the Security Key option</li>
</ol>

<p>
    If you have any difficulties completing this task, please contact <?= yHtml::encode($supportName) ?> at
    <?= yHtml::encode($supportEmail) ?>.
</p>

<p>Thanks,</p>

<p><i><?= yHtml::encode($emailSignature) ?></i></p>
