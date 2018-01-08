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
 * @var string $mfaTypeDisabled
 * @var bool   $isMfaEnabled
 * @var array  $mfaOptions
 */
?>
<p>Dear <?= yHtml::encode($displayName) ?>,</p>

<p>
    You have disabled the ability to use a <?= yHtml::encode($mfaTypeDisabled) ?> for 2-Step Verification when logging in using
    your <?= yHtml::encode($idpDisplayName) ?> Identity account. If this was not intentional, go to
    <?= yHtml::encode($passwordProfileUrl) ?>, log in if needed, and add it back to your account.
</p>
<p>
    You can continue to use 2-Step Verification using the following option(s):
</p>
<ol>
    <?php
        foreach ($mfaOptions as $mfa) {
            echo '<li>' . yHtml::encode($mfa->getReadableType()) . '</li>';
        }
    ?>
</ol>
<p>
    If you did not do this it could be a sign someone else has compromised your account. Please contact
    yHtml::encode(<?= $supportName ?>) at <?= yHtml::encode($supportEmail) ?> as soon as possible to report the incident.
</p>
<p><i><?= yHtml::encode($emailSignature) ?></i></p>