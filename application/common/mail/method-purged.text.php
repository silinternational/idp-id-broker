<?php

/**
 * @var string $displayName        Email address to be verified. Provided by user profile.
 * @var string $alternateAddress   Email address to be verified. Generated at runtime.
 * @var string $numberVerified     Number of verified recovery options. Generated at runtime.
 * @var string $idpDisplayName     Display name of IDP instance. Provided by environment variable IDP_DISPLAY_NAME.
 * @var string $supportName        Help center name.  Provided by environment variable SUPPORT_NAME.
 * @var string $supportEmail       Help center email address.  Provided by environment variable SUPPORT_EMAIL.
 * @var string $helpCenterUrl      Help center website URL.  Provided by environment variable HELP_CENTER_URL.
 * @var string $emailSignature     Email signature. Provided by environment variable EMAIL_SIGNATURE.
 * @var string $passwordProfileUrl URL of password manager. Provided by environment variable PASSWORD_PROFILE_URL.
 */
?>
Dear <?= $displayName ?>,

You recently requested to add an email address, <?= $alternateAddress ?>,
as an alternate method for verifying your identity should you ever need to reset your
<?= $idpDisplayName ?> account password. Because it was not verified before
its expiration time, it has been removed from your <?= $idpDisplayName ?> account.

<?php if ($numberVerified === 0) : ?>
Please go to your profile page at
<?= $passwordProfileUrl . PHP_EOL ?>
to add an alternate password recovery email.
<?php else : ?>
If you still wish to add this email, please go to your profile page here:
<?= $passwordProfileUrl . PHP_EOL ?>
<?php endif ?>

If you have any questions, you can visit our Help Center at
<?= $helpCenterUrl . PHP_EOL ?>

Thanks,
<?= $emailSignature ?>
