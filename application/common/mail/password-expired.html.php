<?php
use yii\helpers\Html as yHtml;

/**
 * @var string $firstName
 * @var string $displayName
 * @var string $username
 * @var string $email
 * @var string $passwordExpiresUtc
 * @var string $emailSignature
 * @var string $helpCenterUrl
 * @var string $idpDisplayName
 * @var string $passwordProfileUrl
 * @var string $supportEmail
 * @var string $supportName
 * @var bool   $isMfaEnabled
 * @var string $passwordLastChanged
 */

$passwordForgotUrl = $passwordProfileUrl . '/password/forgot';
$pwExtension = ltrim(\Yii::$app->params['passwordMfaLifespanExtension'], '+');

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
    Password last changed on: <?=yHtml::encode($passwordLastChanged)?><br>
    Password expired on: <?=yHtml::encode($passwordExpiresUtc)?>
</p>
<?php if (!$isMfaEnabled) { ?>
<p>
    If you enable 2-Step Verification, your password expiration will be extended
    by <?= yHtml::encode($pwExtension) ?>. This would take effect immediately, so you would not have to change
    your password at this time.
</p>
<strong>Instructions to set up 2-Step Verification:</strong>
<ol>
    <li>Go to <?=yHtml::a(yHtml::encode($passwordProfileUrl), $passwordProfileUrl)?></li>
    <li>Under 2-Step Verification, set up the options that suit you best (USB Security Key, Authenticator App, and/or
        Printable Codes)</li>
    <li>Log out and log in again to see how it works and to have it remember your computer for 30 days. Note that
        logging out will undo the "Remember this computer" setting.</li>
</ol>
    <?php if (!empty($helpCenterUrl)) { ?>
        <p>
            To learn more about 2-Step Verification go to <?=yHtml::a(yHtml::encode($helpCenterUrl), $helpCenterUrl)?>
        </p>
    <?php } ?>
<?php } ?>
<p>
    If you have any difficulties completing this task, please contact <?=yHtml::encode($supportName)?> at
    <?=yHtml::encode($supportEmail)?>.
</p>
<p>
    Thanks,
</p>
<p>
    <i><?=nl2br(yHtml::encode($emailSignature), false)?></i>
</p>
