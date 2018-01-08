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
 */
?>
Dear <?= $displayName ?>,

2-Step Verification has been disabled on your <?= $idpDisplayName ?> Identity account. If this was not intentional
follow the instructions below to setup 2-Step Verification on your account.

If you did not do this it could be a sign someone else has compromised your account.
Please contact <?= $supportName ?> at <?= $supportEmail ?> as soon as possible to report the incident.

Instructions to set up 2-Step Verification:
-------------------------------------------
1. Go to <?= $passwordProfileUrl ?>
2. Under 2-Step Verification, set up the options that suit you best (Security Key, Smartphone App, and/or
Printable Codes)
3. Log out and log in again to see how it works and to have it remember your computer for 30 days. Note that
logging out will undo the "Remember this computer" setting.

To learn more about 2-Step Verification go to <?= $helpCenterUrl ?>

If you have any difficulties completing this task, please contact <?= $supportName ?> at
<?= $supportEmail ?>.

<?= $emailSignature ?>
