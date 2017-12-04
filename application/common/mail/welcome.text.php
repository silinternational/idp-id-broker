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

Congratulations! You have logged into your new <?= $idpDisplayName ?> account for the first time.
<?= $idpDisplayName ?> is in the process of transitioning from logging into websites using
an Insite account to this new "<?= $idpDisplayName ?> account". Starting in January 2018 the option
to log in to websites using your Insite account will go away. This new <?= $idpDisplayName ?>
account is not the same as your email account or computer account. It is a new corporate identity for
logging into many websites used by <?= $idpDisplayName ?>.

Password expires on: <?= $passwordExpiresUtc ?>

Please be sure to configure recovery methods for
the potential event that you forget your password. You can reset your password using your email address,
<?= $email ?>, but you can also add other addresses and even phone numbers for SMS verification.

Instructions to add recovery methods:
-------------------------------------
1. Go to <?= $passwordProfileUrl ?>.
2. Click the "Add" button next to "Password recovery methods."
3. Select the option for either an Email or Phone recovery method.
4. Enter the email address or phone number you wish to use and click "Send Code"
5. If you entered an email address, check the inbox for that email address for a new email and retrieve the code
   from that email.
6. If you entered a phone number, watch for a text message or phone call and take note of the code you receive.
7. Enter the verification code into the form on your screen and click "Verify".

Enable 2-Step Verification for enhanced security (recommended)
==============================================================
Using 2-Step Verification can help keep bad guys out, even if they have your password. With 2-Step Verification, you'll
protect your account with something you know (your password) and something you have (your phone or Security Key).
Setup is easy and with the option to remember your computer for 30 days at a time, you'll only need to use the second
step every month or so, but anyone trying to hack into your account would need both steps. This not only increases the
security of your own account, it increases the privacy and protection of your colleagues by keeping intruders out of
the systems that have sensitive information about many of us.

Instructions to set up 2-Step Verification:
-------------------------------------------
1. Go to <?= $passwordProfileUrl ?>
2. Under 2-Step Verification, set up the options that suit you best (USB Security Key, Smartphone App, and/or
   Printable Codes)
3. Log out and log in again to see how it works and to have it remember your computer for 30 days. Note that
   logging out will undo the "Remember this computer" setting.

To learn more about 2-Step Verification go to <?= $helpCenterUrl ?>

If you have any difficulties completing this task, please contact <?= $supportName ?> at
<?= $supportEmail ?>.


<?= $emailSignature ?>
