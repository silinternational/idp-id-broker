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
 * @var int    $numRemainingCodes
 */
?>
<p>Dear <?= yHtml::encode($displayName) ?>,</p>

<p>
    You only have <?= $numRemainingCodes ?> Printable Code(s) remaining for use with 2-Step Verification on your
    <?= yHtml::encode($idpDisplayName) ?> Identity account. Now may be a good time to generate new codes to ensure you do not run out.
</p>

<p>
    Instructions to generate new Printable Codes:
</p>
<ol>
    <li>Go to <?= yHtml::encode($passwordProfileUrl) ?></li>
    <li>Login if needed</li>
    <li>Under 2-Step Verification, click on CREATE NEW next to the Printable Codes option.</li>
    <li>Either print the codes out, download them to a safe place, or copy and paste them into a safe place.</li>
</ol>

<p>
    Treat these codes as you would your password and keep them safe. Also note that these codes are one-time use only and
    after generating new codes, any previously unused codes are no longer valid.
</p>

<p>
    If you have any difficulties completing this task, please contact <?= yHtml::encode($supportName) ?> at
    <?= yHtml::encode($supportEmail) ?>.
</p>

<p><i><?= yHtml::encode($emailSignature) ?></i></p>