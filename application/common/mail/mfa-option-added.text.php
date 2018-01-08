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
 * @var array  $mfaOptions
 */
?>
Dear <?= $displayName ?>,

You have added a new option for 2-Step Verification to your <?= $idpDisplayName ?> Identity account.
When you are prompted for 2-Step Verification in the future, you will now have the option to choose a different
method of 2-Step Verification if you do not have access to the primary method.

You now have the following 2-Step Verification options enabled:
<?php
    foreach ($mfaOptions as $mfa) {
       echo ' - ' . $mfa->getReadableType();
    }
?>

If you have any questions or concerns about this, please contact <?= $supportName ?> at
<?= $supportEmail ?>.

<?= $emailSignature ?>
