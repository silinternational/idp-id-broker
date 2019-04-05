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
 * @var bool   $hasRecoveryMethods
 */

 $forgotUrl = $passwordProfileUrl . '/password/forgot';
?>
<p>
    Dear <?=yHtml::encode($displayName)?>,
</p>
<p>
    Thank you for setting your <?=yHtml::encode($idpDisplayName)?> identity
    account password. Below is some important information about this account
    that you may want to keep for future reference.
</p>

<ul>

    <li>
        <strong>Username:</strong> <?=yHtml::encode($username)?>
    </li>

    <li>
        <strong>To update profile, go to:</strong>
        <?=yHtml::a(yHtml::encode($passwordProfileUrl), $passwordProfileUrl)?>
    </li>

    <li>
        <strong>If you forget your password, go to:</strong>
        <?=yHtml::a(yHtml::encode($forgotUrl), $forgotUrl)?>
    </li>

    <li>
        <strong>Help & FAQs:</strong>
        <?=yHtml::a(yHtml::encode($helpCenterUrl), $helpCenterUrl)?>
    </li>
    
    <li>
        <strong>Contact Support:</strong><?=yHtml::encode($supportEmail)?>
    </li>

</ul>

<p>
    Thanks,
</p>
<p>
    <i><?=yHtml::encode($emailSignature)?></i>
</p>
