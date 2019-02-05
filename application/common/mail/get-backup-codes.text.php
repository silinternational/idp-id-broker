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

You currently only have one method of 2-Step Verification configured for your
<?= $idpDisplayName ?> Identity account and have not generated Printable
Codes as a backup. You are not required to have Printable Codes but we do
recommend them as a backup in case you lose your other option. If you do not
want Printable Codes you can delete and ignore this email.

Instructions to generate Printable Codes:
-----------------------------------------
   1. Go to <?= $passwordProfileUrl . PHP_EOL ?>
   2. Log in if needed
   3. In the 2-Step Verification section, click "CREATE" next to Printable
      Backup Codes
   4. Either print the codes out, download them to a safe place, or copy and
      paste them into a safe place.

Treat these codes as you would your password and keep them safe. Also note
that these codes are one-time use only. If you ever lose, misplace, or use
them up you can revisit <?= $passwordProfileUrl . PHP_EOL ?> to generate
new codes (which also invalidates any remaining previous codes).

If you have any difficulties completing this task, please contact <?= $supportName . PHP_EOL ?>
at <?= $supportEmail ?>.

Thank you,
<?= $emailSignature ?>
