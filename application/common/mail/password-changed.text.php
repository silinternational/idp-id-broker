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

The password for your <?= $idpDisplayName ?> account has been changed. If you
did not make this change please contact <?= $supportName ?> at
<?= $supportEmail ?> immediately to let us know.

Please remember that this account will be your primary means for logging into
many corporate applications. It is also important to note that this account
(username and password) is unique and will not be kept in sync with any other
accounts you have.

Password changed on: <?= $lastChangedUtc . PHP_EOL ?>
Password expires on: <?= $passwordExpiresUtc . PHP_EOL ?>

If you have not already done so, it is highly recommended that you configure
recovery methods for the potential event that you forget your password. You
can reset your password using your primary email address, <?= $email ?>,
but you can also add other addresses for verification.

Instructions to add recovery methods:
-------------------------------------
1. Go to <?= $passwordProfileUrl . PHP_EOL ?>
2. Click the "Add" button next to "Password recovery"
3. Enter the email address you wish to use and click the add button
4. Check for a new email in the inbox for that address and click the link
   in that email.

<?php
if (! $isMfaEnabled) {
    ?>

Enable 2-Step Verification (please)
===================================
2-Step Verification can help keep bad guys out, even if they have your
password. With 2-Step Verification, you'll protect your account with
something you know (your password) and something you have (your phone or
Security Key). Setup is easy and with the option to remember your computer
for 30 days at a time, you’ll only need to use the second step every month or
so, but anyone trying to hack into your account would need both steps. This
not only increases the security of your own account, it increases the privacy
and protection of your colleagues by keeping intruders out of the systems
that have sensitive information about many of us.

Instructions to set up 2-Step Verification:
-------------------------------------------
1. Go to <?= $passwordProfileUrl . PHP_EOL ?>
2. Under 2-Step Verification, set up the options that suit you best (USB
   Security Key, Smartphone App, and/or Printable Codes)
3. Log out and log in again to see how it works and to have it remember your
   computer for 30 days. Note that logging out will undo the "Remember this
   computer" setting.

To learn more about 2-Step Verification go to <?= $helpCenterUrl ?>

    <?php
}
?>

If you have any difficulties completing this task, please contact
<?= $supportName ?> at <?= $supportEmail ?>.

Thanks,
<?= $emailSignature ?>
