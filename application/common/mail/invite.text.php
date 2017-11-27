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

A new <?= $idpDisplayName ?> account has been created for you and is ready for 
you to create your password. After creating your password you'll also 
be given the opportunity to set up recovery methods in case you ever 
forget your password. We highly recommended that you set up at least 
one or two recovery methods. You can also enhance the security of your 
account by enabling 2-Step Verification which will help ensure bad 
guys cannot get into your account even if they guess your password.

If you have any difficulties completing this task, please contact 
<?= $supportName ?> at: <?= $supportEmail ?>

Instructions:
-------------
1. Go to <?= $passwordForgotUrl ?>
2. Enter your username, <?= $username ?>
3. Check the box next to "I'm not a robot" and click "Continue"
4. Check your <?= $email ?> email inbox for a message 
   with the subject "<?= $idpDisplayName ?> password reset request"
5. Click the link in the email
6. Enter a new password that meets the requirements as described on 
   the page
7. Enter your password again to confirm it
8. Click the "Change" button to set your new password

Please note that this <?= $idpDisplayName ?> account (username and password) is 
unique and its password is not synchronized with any other accounts 
you may have. 

Thank you,

<?= $emailSignature ?>
