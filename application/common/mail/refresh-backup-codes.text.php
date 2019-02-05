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
 * @var string $passwordForgotUrl
 * @var string $passwordProfileUrl
 * @var string $supportEmail
 * @var string $supportName
 * @var bool   $isMfaEnabled
 * @var int    $numRemainingCodes
 */
?>
Dear <?= $displayName ?>,

<?php if ($numRemainingCodes == 0) : ?>
You have no remaining Printable Codes for use with 2-Step Verification. Now
would be a very good time to generate new codes to ensure you can continue to
access your <?= $idpDisplayName ?> Identity account.
<?php elseif ($numRemainingCodes == 1) : ?>
You only have 1 Printable Code remaining for use with 2-Step Verification on
your <?= $idpDisplayName ?> Identity account. Now may be a good
time to generate new codes to ensure you do not run out.
<?php else : ?>
You only have <?= $numRemainingCodes ?> Printable Codes remaining for use with 2-Step Verification on
your <?= $idpDisplayName ?> Identity account. Now may be a good
time to generate new codes to ensure you do not run out.
<?php endif; ?>

Instructions to generate new Printable Codes:
---------------------------------------------
   1. Go to <?= $passwordProfileUrl . PHP_EOL ?>
   2. Login if needed
   3. Under 2-Step Verification, click on CREATE next to the Printable Codes option.
   4. Either print the codes out, download them to a safe place, or copy and
      paste them into a safe place.

Treat these codes as you would your password and keep them safe. Also note
that these codes are one-time use only and after generating new codes, any
previously unused codes are no longer valid.

If you have any difficulties completing this task, please contact <?= $supportName ?> at
<?= $supportEmail ?>.

Thanks,
<?= $emailSignature ?>
