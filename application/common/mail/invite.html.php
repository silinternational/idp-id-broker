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
<p>Dear <?= yHtml::encode($displayName) ?>,</p>

<p>A new <?= yHtml::encode($idpDisplayName) ?> account has been created for you and is ready for you to create your password. After creating your password you'll also be given the opportunity to set up recovery methods in case you ever forget your password. We highly recommended that you set up at least one or two recovery methods. You can also enhance the security of your account by enabling 2-Step Verification which will help ensure bad guys cannot get into your account even if they guess your password.</p>

<p>
  If you have any difficulties completing this task, please contact
  <?= yHtml::encode($supportName) ?> at: <?= yHtml::encode($supportEmail) ?>
</p>

<p><b>Instructions:</b></p>
<ol>
  <li>Go to <?= yHtml::encode($passwordForgotUrl) ?></li>
  <li>Enter your username, <?= yHtml::encode($username) ?></li>
  <li>Check the box next to "I'm not a robot" and click "Continue"</li>
  <li>Check your <?= yHtml::encode($email) ?> email inbox for a message with the subject "<?= yHtml::encode($idpDisplayName) ?> password reset request"</li>
  <li>Click the link in the email</li>
  <li>Enter a new password that meets the requirements as described on the page</li>
  <li>Enter your password again to confirm it</li>
  <li>Click the "Change" button to set your new password</li>
</ol>

<p>Please note that this <?= yHtml::encode($idpDisplayName) ?> account (username and password) is unique and its password is not synchronized with any other accounts you may have.</p>

<p>Thank you,</p>

<p><i><?= yHtml::encode($emailSignature) ?></i></p>
