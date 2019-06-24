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
 */
?>
<p>Dear <?= yHtml::encode($displayName) ?>,</p>

<p>
    You currently only have one method of 2-Step Verification configured for your <?= yHtml::encode($idpDisplayName) ?>
    Identity account and have not generated Printable Codes as a backup. You are not required to have Printable Codes
    but we do recommend them as a backup in case you lose your other option. If you do not want Printable Codes, you can
    delete and ignore this email.
</p>
<p>
    Instructions to generate Printable Codes:
</p>
<ol>
    <li>Go to <?=yHtml::a(yHtml::encode($passwordProfileUrl), $passwordProfileUrl)?>.</li>
    <li>Log in if needed</li>
    <li>In the 2-Step Verification section, click "CREATE" next to Printable Backup Codes</li>
    <li>Either print the codes out, download them to a safe place, or copy and paste them into a safe place.</li>
</ol>
<p>
    Treat these codes as you would your password and keep them safe. Also note that these codes are one-time use only.
    If you ever lose, misplace, or use them up, you can revisit
    <?=yHtml::a(yHtml::encode($passwordProfileUrl), $passwordProfileUrl)?> to generate new codes (which also
    invalidates any remaining previous codes).
</p>
<p>
    If you have any difficulties completing this task, please contact <?= yHtml::encode($supportName) ?> at
    <?= yHtml::encode($supportEmail) ?>.
</p>

<p>Thank you,</p>

<p><i><?= nl2br(yHtml::encode($emailSignature), false) ?></i></p>
