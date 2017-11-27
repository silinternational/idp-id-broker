<?php
use common\helpers\MySqlDateTime;
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
<p>
    Dear <?=yHtml::encode($displayName)?>,
</p>
<p>
    The password for your <?=yHtml::encode($idpDisplayName)?> account has been changed. If you did not make this change
    please contact <?=yHtml::encode($supportName)?> at <?=yHtml::encode($supportEmail)?> immediately to let us know.
</p>
<p>
    Please remember that this account will be your primary means for logging into many corporate
    applications. It is also important to note that this account (username and password) is unique and will not be kept
    in sync with any other accounts you have.
</p>
<p>
    Password changed on: <?=yHtml::encode(MySqlDateTime::formatDateForHumans($lastChangedUtc))?><br />
    Password expires on: <?=yHtml::encode(MySqlDateTime::formatDateForHumans($passwordExpiresUtc))?>
</p>
<p>
    If you have not already done so, it is highly recommended that you configure <strong>recovery methods</strong> for
    the potential event that you forget your password. You can reset your password using your email address,
    <?=yHtml::encode($email)?>, but you can also add other addresses and even phone numbers for SMS verification.
</p>
<p>
    <strong>Instructions to add recovery methods:</strong>
</p>
<ol>
    <li>Go to <?=yHtml::a(yHtml::encode($passwordProfileUrl), $passwordProfileUrl)?>.</li>
    <li>Click the "Add" button next to Recovery Methods.</li>
    <li>Select the option for either an Email or Phone recovery method.</li>
    <li>Enter the email address or phone number you wish to use and click "Send Code"</li>
    <li>If you entered an email address, check the inbox for that email address for a new email and retrieve the code
        from that email.</li>
    <li>If you entered a phone number, watch for a text message or phone call and take note of the code you receive.</li>
    <li>Enter the verification code into the form on your screen and click "Verify".</li>

</ol>

<?php
if ( ! $isMfaEnabled) {
?>
<p>
    <strong>Enable 2-Step Verification for enhanced security</strong>
    2-Step Verification can help keep bad guys out, even if they have your password. With 2-Step Verification, you'll
    protect your account with something you know (your password) and something you have (your phone or Security Key).
    Setup is easy and with the option to remember your computer for 30 days at a time, you’ll only need to use the second
    step every month or so, but anyone trying to hack into your account would need both factors.
</p>
<strong>Instructions to set up 2-Step Verification:</strong>
<ol>
    <li>Go to <?=yHtml::a(yHtml::encode($passwordProfileUrl), $passwordProfileUrl)?></li>
    <li>Under 2-Step Verification, set up the options that suite you best (USB Security Key, Smartphone App, and/or
        Printable Backup Codes)</li>
    <li>Log out and log in again to see how it works and to have it remember your computer for 30 days. Note that
        logging out will undo the "Remember this computer" setting.</li>
</ol>
<p>
    To learn more about 2-Step Verification go to <?=yHtml::a(yHtml::encode($helpCenterUrl), $helpCenterUrl)?>
</p>
<?php
}
?>
<p>
    If you have any difficulties completing this task, please contact <?=yHtml::encode($supportName)?> at
    <?=yHtml::encode($supportEmail)?>.
</p>
<p>
    <?=$emailSignature?>
</p>
