<?php
use yii\helpers\Html as yHtml;

/**
 * @var string $displayName     Email address to be verified. Provided by user profile.
 * @var string $toAddress       Email address to be verified. Generated at runtime.
 * @var string $idpDisplayName  Display name of IDP instance. Provided by environment variable IDP_DISPLAY_NAME.
 * @var string $code            Verification code. Generated at runtime.
 * @var string $supportName     Help center name.  Provided by environment variable SUPPORT_NAME.
 * @var string $supportEmail    Help center email address.  Provided by environment variable SUPPORT_EMAIL.
 * @var string $helpCenterUrl   Help center website URL.  Provided by environment variable HELP_CENTER_URL.
 * @var string $emailSignature  Email signature. Provided by environment variable EMAIL_SIGNATURE.
 */
?>
<p>Dear <?= yHtml::encode($displayName) ?>,</p>

<p>
    Someone recently requested to add this email address, <?= yHtml::encode($toAddress); ?>,
    as a method for verifying themselves should they need to reset their
    <?= yHtml::encode($idpDisplayName); ?> account password. If this was you, you can use the verification code
    below to add it to your account.
</p>
<p>
    Verification Code: <?= yHtml::encode($code); ?>
</p>
<p>
    If you did not do this it could be a sign someone else has compromised your account.
    Please contact <?= yHtml::encode($supportName) ?> at <?= yHtml::encode($supportEmail) ?> as soon as possible to report the incident.
</p>
<p>
    To keep your account secure, please don't forward this email to anyone.
    See our Help Center for <a href="<?= yHtml::encode($helpCenterUrl); ?>">more security tips</a>.
</p>
<p>
    Thanks!
</p>
<p>
    <i><?= yHtml::encode($emailSignature); ?></i>
</p>
