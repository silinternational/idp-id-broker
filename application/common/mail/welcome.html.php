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
?>
<p>
    Dear <?=yHtml::encode($displayName)?>,
</p>
<p>
    Congratulations! You have logged into your new <?=yHtml::encode($idpDisplayName)?>
    account for the first time. Please remember that this account will be your
    primary means for logging into many corporate applications. It is also
    important to note that this account (username and password) is unique and
    will not be kept in sync with any other accounts you have.
</p>
<p>
    Your password expires on: <?=yHtml::encode($passwordExpiresUtc)?>
</p>

<?php if ($hasRecoveryMethods == false) : ?>
<p>
    Please be sure to configure <strong>recovery methods</strong> for
    the potential event that you forget your password.  You can reset your password
    using your primary email address, <?=yHtml::encode($email)?>, but you can also
    add other addresses for verification.
</p>
<p>
    <strong>Instructions to add recovery methods:</strong>
</p>
<ol>
    <li>Go to <?=yHtml::a(yHtml::encode($passwordProfileUrl), $passwordProfileUrl)?>.</li>
    <li>Click the "Add" button next to <i>Password recovery</i>.</li>
    <li>Enter the email address you wish to use and click the add button</li>
    <li>Check for a new email in the inbox for that address and click the link in that email.</li>
</ol>
<?php endif; ?>

<?php if ($isMfaEnabled == false) : ?>
<p>
    <strong>Enable 2-Step Verification</strong> (please)
</p>
<p>
    2-Step Verification can help keep bad guys out, even if they have your
    password. With 2-Step Verification, you'll protect your account with
    something you know (your password) and something you have (your phone or
    Security Key). Setup is easy and with the option to remember your computer
    for 30 days at a time, you’ll only need to use the second step every month or
    so, but anyone trying to hack into your account would need both steps. This
    not only increases the security of your own account, it increases the privacy
    and protection of your colleagues by keeping intruders out of the systems
    that have sensitive information about many of us.
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
<?php endif; ?>

<?php if ($isMfaEnabled == false || $hasRecoveryMethods == false) : ?>
<p>
    If you have any difficulties completing this task, please contact <?=yHtml::encode($supportName)?> at
    <?=yHtml::encode($supportEmail)?>.
</p>
<?php endif; ?>

<p>
    Thanks,
</p>
<p>
    <i><?=yHtml::encode($emailSignature)?></i>
</p>
