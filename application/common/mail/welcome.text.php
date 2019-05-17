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
 * @var bool   $hasRecoveryMethods
 */
?>
Dear <?= $displayName ?>,

Thank you for setting your <?= $idpDisplayName ?> Identity
account password. Below is some important information about this account
that you may want to keep for future reference.

- Username: <?= $username . PHP_EOL ?>
- To update profile, go to: <?= $passwordProfileUrl  . PHP_EOL ?>
- If you forget your password, go to: <?= $passwordProfileUrl  . '/password/forgot' . PHP_EOL ?>
<?php if (! empty($helpCenterUrl)) : ?>- Help & FAQs: <?= $helpCenterUrl  . PHP_EOL ?><?php endif; ?>
- Contact Support: <?= $supportEmail  . PHP_EOL ?>

Thanks,
<?= $emailSignature ?>
