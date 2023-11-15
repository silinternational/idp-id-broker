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
 * @var string $mfaTypeDisabled
 * @var bool   $isMfaEnabled
 * @var array  $mfaOptions
 */
?>
<p>Dear <?= yHtml::encode($displayName) ?>,</p>

<p>
    You have disabled the ability to use your <?= yHtml::encode($mfaTypeDisabled) ?> for 2-Step
    Verification when logging in using your
    <?= yHtml::encode($idpDisplayName) ?> Identity account. If this was not intentional, go to
    <?= yHtml::a(yHtml::encode($passwordProfileUrl), $passwordProfileUrl) ?>,
    log in if needed, and add it back to your account.
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
    If you did not do this, it could be a sign someone else has compromised your account. Please contact
    <?= yHtml::encode($supportName) ?> at <?= yHtml::encode($supportEmail) ?>
    as soon as possible to report the incident.
</p>
<p>
    Thanks,
</p>
<p><i><?= nl2br(yHtml::encode($emailSignature), false) ?></i></p>
