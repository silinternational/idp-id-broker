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
 * @var string $passwordProfileUrl
 * @var string $supportEmail
 * @var string $supportName
 * @var bool   $isMfaEnabled
 */

$passwordForgotUrl = $passwordProfileUrl . '/password/forgot';

?>
<p>
    Dear <?=yHtml::encode($displayName)?>,
</p>
<p>
    The password for your <?=yHtml::encode($idpDisplayName)?> Identity account has
    expired. Please go to
    <?=yHtml::a(yHtml::encode($passwordForgotUrl), $passwordForgotUrl)?> to begin
    the password reset process.
</p>
<p>
    Password last changed on: <?=yHtml::encode($lastChangedUtc)?><br>
    Password expires on: <?=yHtml::encode($passwordExpiresUtc)?>
</p>
<?php if (! $isMfaEnabled) : ?>
<p>
    If you enable 2-Step Verification, your password expiration will be extended
    significantly. To do this, first reset your password as described above, then
    follow these instructions:
</p>
<strong>Instructions to set up 2-Step Verification:</strong>
<ol>
    <li>Go to <?=yHtml::a(yHtml::encode($passwordProfileUrl), $passwordProfileUrl)?></li>
    <li>Under 2-Step Verification, set up the options that suit you best (USB Security Key, Smartphone App, and/or
        Printable Codes)</li>
    <li>Log out and log in again to see how it works and to have it remember your computer for 30 days. Note that
        logging out will undo the "Remember this computer" setting.</li>
</ol>
<p>
    To learn more about 2-Step Verification go to <?=yHtml::a(yHtml::encode($helpCenterUrl), $helpCenterUrl)?>
</p>
<?php endif ?>
<p>
    If you have any difficulties completing this task, please contact <?=yHtml::encode($supportName)?> at
    <?=yHtml::encode($supportEmail)?>.
</p>
<p>
    Thanks,
</p>
<p>
    <i><?=yHtml::encode($emailSignature)?></i>
</p>
