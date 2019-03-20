<?php

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
 * @var string $mfaTypeDisabled
 * @var bool   $isMfaEnabled
 * @var array  $mfaOptions
 */
?>
Dear <?= $displayName ?>,

You have disabled the ability to use your <?= $mfaTypeDisabled ?> for 2-Step
Verification when logging in using your <?= $idpDisplayName ?> Identity
account. If this was not intentional, go to <?= $passwordProfileUrl ?>, log
in if needed, and add it back to your account.

You can continue to use 2-Step Verification using the following option(s):
<?php
foreach ($mfaOptions as $mfa) {
    echo ' - ' . $mfa->getReadableType() . PHP_EOL;
}
?>

If you did not do this, it could be a sign someone else has compromised your
account. Please contact <?= $supportName ?> at <?= $supportEmail ?> as
soon as possible to report the incident.

Thanks,
<?= $emailSignature ?>

