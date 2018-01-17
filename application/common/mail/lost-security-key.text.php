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

We noticed you have a Security Key configured for 2-Step Verification on your <?= $idpDisplayName ?> Identity account
but have not used it recently and instead have been using a different option for 2-Step Verification
(a Smartphone App or Printable Codes).

This email is just a courtesy to check if you have lost your Security Key and to remind you to remove it from your
account to ensure it cannot be used by someone else. If you still have your Security Key and have just been using
other methods recently you can ignore and delete this email.

If you need to remove the Security Key from your Identity account:
   1. Go to <?= $passwordProfileUrl ?>
   2. Log in if needed
   3. Under the 2-Step Verification section, click DISABLE next to the Security Key option

If you have any difficulties completing this task, please contact <?= $supportName ?> at
<?= $supportEmail ?>.

<?= $emailSignature ?>
