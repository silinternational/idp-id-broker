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

There have been too many failed 2-Step Verification attempts on your
<?= $idpDisplayName ?> Identity account. You will need to wait at least 5
minutes and try again.

If you are not currently trying to log into your <?= $idpDisplayName . PHP_EOL ?>
Identity account, it could be a sign someone else is trying to access your
account. Please contact <?= $supportName ?> at <?= $supportEmail ?> as soon
as possible to report the incident.

If you continue to have problems accessing your account, please contact 
<?= $supportName ?> at <?= $supportEmail ?>.

Thanks,
<?= $emailSignature ?>
