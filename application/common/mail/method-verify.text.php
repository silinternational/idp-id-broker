<?php

/**
 * @var string $displayName     Email address to be verified. Provided by user profile.
 * @var string $toAddress       Email address to be verified. Generated at runtime.
 * @var string $idpDisplayName  Display name of IDP instance. Provided by environment variable IDP_DISPLAY_NAME.
 * @var string $uid             Method uid. Generated at runtime.
 * @var string $code            Verification code. Generated at runtime.
 * @var string $supportName     Help center name.  Provided by environment variable SUPPORT_NAME.
 * @var string $supportEmail    Help center email address.  Provided by environment variable SUPPORT_EMAIL.
 * @var string $helpCenterUrl   Help center website URL.  Provided by environment variable HELP_CENTER_URL.
 * @var string $emailSignature  Email signature. Provided by environment variable EMAIL_SIGNATURE.
 * @var string $passwordProfileUrl  URL of password manager. Provided by environment variable PASSWORD_PROFILE_URL.
 */
?>
Dear <?= $displayName ?>,

Someone recently requested to add this email address, <?= $toAddress ?>,
as a method for verifying themselves should they need to reset their
<?= $idpDisplayName ?> Identity account password. If this was you, please go to the
following address to verify this email address:

<?= $passwordProfileUrl . '/password/recovery/' . $uid . '/verify/' . $code . PHP_EOL ?>

If you did not request adding this email address to your account please delete this email
and contact <?= $supportName ?> at <?= $supportEmail ?> as soon as possible to report the
incident.

To maintain security, please don't forward this email to anyone. See
our Help Center at <?= $helpCenterUrl ?> for more security tips.

Thanks,
<?= $emailSignature ?>
