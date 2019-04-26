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
 * @var bool   $isMfaEnabled
 */

$passwordForgotUrl = $passwordProfileUrl . '/password/forgot';

?>
Dear <?= $displayName ?>,

The password for your <?= $idpDisplayName ?> Identity account has expired.
Please go to <?= $passwordForgotUrl ?> to begin the password
reset process.

Password changed on: <?= $lastChangedUtc . PHP_EOL ?>
Password expires on: <?= $passwordExpiresUtc . PHP_EOL ?>

<?php if (! $isMfaEnabled) : ?>
If you enable 2-Step Verification, your password expiration will be extended
significantly. To do this, first reset your password as described above, then
follow these instructions:

Instructions to set up 2-Step Verification:
-------------------------------------------
1. Go to <?= $passwordProfileUrl . PHP_EOL ?>
2. Under 2-Step Verification, set up the options that suit you best (USB
Security Key, Smartphone App, and/or Printable Codes)
3. Log out and log in again to see how it works and to have it remember your
computer for 30 days. Note that logging out will undo the "Remember this
computer" setting.

To learn more about 2-Step Verification go to <?= $helpCenterUrl . PHP_EOL ?>
<?php endif ?>

If you have any difficulties completing this task, please contact
<?= $supportName ?> at <?= $supportEmail ?>.

Thanks,
<?= $emailSignature ?>
