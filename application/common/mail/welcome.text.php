<?php
use yii\helpers\Html as yHtml;

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
Dear <?=yHtml::encode($displayName)?>,

Congratulations! You have logged into your new <?=yHtml::encode($idpDisplayName)?> account for the first time.

Note that this account will be your primary means for logging into many corporate
applications. It is also important to note that this account (username and password) is unique and will not be kept
in sync with any other accounts you have.

Password expires on: <?=yHtml::encode($passwordExpiresUtc)?>

Please be sure to configure recovery methods for
the potential event that you forget your password. You can reset your password using your email address,
<?=yHtml::encode($email)?>, but you can also add other addresses and even phone numbers for SMS verification.

Instructions to add recovery methods:
-------------------------------------
1. Go to <?=yHtml::encode($passwordProfileUrl)?>.
2. Click the "Add" button next to Recovery Methods.
3. Select the option for either an Email or Phone recovery method.
4. Enter the email address or phone number you wish to use and click "Send Code"
5. If you entered an email address, check the inbox for that email address for a new email and retrieve the code
   from that email.
6. If you entered a phone number, watch for a text message or phone call and take note of the code you receive.
7. Enter the verification code into the form on your screen and click "Verify".
    
Enable 2-Step Verification for enhanced security
================================================
2-Step Verification can help keep bad guys out, even if they have your password. With 2-Step Verification, you'll
protect your account with something you know (your password) and something you have (your phone or Security Key).
Setup is easy and with the option to remember your computer for 30 days at a time, you’ll only need to use the second
step every month or so, but anyone trying to hack into your account would need both factors.
    
Instructions to set up 2-Step Verification:
-------------------------------------------
1. Go to <?=yHtml::encode($passwordProfileUrl)?>
2. Under 2-Step Verification, set up the options that suit you best (USB Security Key, Smartphone App, and/or
   Printable Codes)
3. Log out and log in again to see how it works and to have it remember your computer for 30 days. Note that
   logging out will undo the "Remember this computer" setting.

To learn more about 2-Step Verification go to <?=yHtml::encode($helpCenterUrl)?>

If you have any difficulties completing this task, please contact <?=yHtml::encode($supportName)?> at
<?=yHtml::encode($supportEmail)?>.


<?=yHtml::encode($emailSignature)?>
