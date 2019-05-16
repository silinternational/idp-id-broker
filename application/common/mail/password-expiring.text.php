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
?>
Dear <?= $displayName ?>,

The password for your <?= $idpDisplayName ?> Identity account is about to
expire. Please go to your account profile at <?= $passwordProfileUrl . PHP_EOL ?>
and login to change your password.

Password changed on: <?= $lastChangedUtc . PHP_EOL ?>
Password expires on: <?= $passwordExpiresUtc . PHP_EOL ?>

<?php if ($isMfaEnabled) : ?>
If you enable 2-Step Verification, your password expiration will be extended
significantly. This would take effect immediately, so you would not have to change
your password at this time.
<?php endif ?>

If you have any difficulties completing this task, please contact
<?= $supportName ?> at <?= $supportEmail ?>.

Thanks,
<?= $emailSignature ?>
