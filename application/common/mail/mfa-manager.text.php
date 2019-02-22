<?php
/**
 * @var string $displayName     Name of user. Provided by user profile.
 * @var string $firstName       First name of user. Provided by user profile.
 * @var string $idpDisplayName  Display name of IDP instance. Provided by environment variable IDP_DISPLAY_NAME.
 * @var string $code            Rescue code. Generated at runtime.
 * @var string $supportName     Help center name.  Provided by environment variable SUPPORT_NAME.
 * @var string $supportEmail    Help center email address.  Provided by environment variable SUPPORT_EMAIL.
 * @var string $helpCenterUrl   Help center website URL.  Provided by environment variable HELP_CENTER_URL.
 * @var string $emailSignature  Email signature. Provided by environment variable EMAIL_SIGNATURE.
 */
?>
Hello,

<?= $displayName ?> has requested your assistance in accessing their
<?= $idpDisplayName ?> account. This email contains a backup code they need
to proceed with login. Please contact them directly to ensure that you are
only providing the following backup code to them and not to someone else.

Backup Code: <?= $code . PHP_EOL ?>

If <?= $firstName ?> did not do this, it could be a sign someone else has
compromised their account. Please contact <?= $supportName . PHP_EOL ?>
at <?= $supportEmail ?> as soon as possible to report the incident.

To maintain security, please don't forward this email to anyone. See our Help
Center at <?= $helpCenterUrl ?> for more security tips.

Thanks,
<?= $emailSignature; ?>
