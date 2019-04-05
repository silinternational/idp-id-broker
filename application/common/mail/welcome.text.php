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

<p>
    Thank you for setting your <?= $idpDisplayName ?> identity
    account password. Below is some important information about this account
    that you may want to keep for future reference.
</p>

<ul>

    <li><strong>Username:</strong><?= $username ?></li>
    <li><strong>To update profile, go to:</strong><?= $passwordProfileUrl ?></li>
    <li><strong>If you forget your password, go to:</strong><?= $passwordProfileUrl  . '/password/forgot'?></li>
    <li><strong>Help & FAQs:</strong><?= $helpCenterUrl ?></li>
    <li><strong>Contact Support:</strong><?= $supportEmail ?></li>

</ul>

Thanks,
<?= $emailSignature ?>
