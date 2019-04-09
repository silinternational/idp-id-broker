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
Dear <?= $displayName?>,

Congratulations! You have enabled 2-Step Verification on your <?= $idpDisplayName . PHP_EOL ?>
Identity account. This is a great way to protect access to your account as
well as to keep bad guys out of our corporate systems.

The next time you log in to a site using your <?= $idpDisplayName ?> Identity
account you will be prompted for 2-Step Verification. On that screen you'll
see a checkbox already checked to remember your browser for 30 days. If you
leave it checked, you will only be prompted for 2-Step Verification once a
month or so.

If you have not already done so, we recommend configuring at least two
options for 2-Step Verification in case one of the options is not available
to you when logging in. Having more than one method is not required though,
just recommended.

Thanks,
<?= $emailSignature ?>
