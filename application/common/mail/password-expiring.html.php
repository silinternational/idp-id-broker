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

$pwExtension = ltrim(\Yii::$app->params['passwordMfaLifespanExtension'], '+');
?>
<p>
    Dear <?=yHtml::encode($displayName)?>,
</p>
<p>
    The password for your <?=yHtml::encode($idpDisplayName)?> Identity account is about to
    expire. Please go to your account profile at
    <?=yHtml::a(yHtml::encode($passwordProfileUrl), $passwordProfileUrl)?> and login to
    change your password.
</p>
<p>
    Password changed on: <?=yHtml::encode($passwordLastChanged)?><br>
    Password expires on: <?=yHtml::encode($passwordExpiresUtc)?>
</p>
<?php if (!$isMfaEnabled) : ?>
<p>
    If you enable 2-Step Verification, your password expiration will be extended
    by <?= yHtml::encode($pwExtension) ?>. This would take effect immediately, so you would not have to change
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
    <i><?=nl2br(yHtml::encode($emailSignature), false)?></i>
</p>
