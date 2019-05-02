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
?>
<p>
    Dear <?=yHtml::encode($displayName)?>,
</p>
<p>
    The password for your <?=yHtml::encode($idpDisplayName)?> Identity account is about to
    expire. Please go to your account profile at
    <?=yHtml::a(yHtml::encode($passwordProfileUrl), $passwordProfileUrl)?> and login, if
    necessary, to change your password.
</p>
<p>
    Password changed on: <?=yHtml::encode($lastChangedUtc)?><br>
    Password expires on: <?=yHtml::encode($passwordExpiresUtc)?>
</p>
<?php if (! $isMfaEnabled) : ?>
<p>
    If you enable 2-Step Verification, your password expiration will be extended
    significantly. This would take effect immediately, so you would not have to change
    your password at this time.
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