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
 * @var int    $numRemainingCodes
 */
?>
<p>Dear <?= yHtml::encode($displayName) ?>,</p>

<p>
    <?php if ($numRemainingCodes == 0) : ?>
        You have no remaining Printable Codes for use with 2-Step Verification.
        Now would be a very good time to generate new codes to ensure you can continue to access
        your <?= yHtml::encode($idpDisplayName) ?> Identity account.
    <?php elseif ($numRemainingCodes == 1) : ?>
        You only have 1 Printable Code remaining for use with 2-Step Verification
        on your <?= yHtml::encode($idpDisplayName) ?> Identity account.
        Now may be a good time to generate new codes to ensure you do not run out.
    <?php else : ?>
        You only have <?= $numRemainingCodes ?> Printable Codes remaining for use with 2-Step Verification
        on your <?= yHtml::encode($idpDisplayName) ?> Identity account.
        Now may be a good time to generate new codes to ensure you do not run out.
    <?php endif; ?>

</p>

<p>
    Instructions to generate new Printable Codes:
</p>
<ol>
    <li>Go to <?=yHtml::a(yHtml::encode($passwordProfileUrl), $passwordProfileUrl)?>.</li>
    <li>Login if needed</li>
    <li>Under 2-Step Verification, click on REPLACE next to the Printable Codes option.</li>
    <li>Either print the codes out, download them to a safe place, or copy and paste them into a safe place.</li>
</ol>

<p>
    Treat these codes as you would your password and keep them safe. Also note
    that these codes are one-time use only and after generating new codes, any
    previously unused codes are no longer valid.
</p>

<p>
    If you have any difficulties completing this task, please contact <?= yHtml::encode($supportName) ?> at
    <?= yHtml::encode($supportEmail) ?>.
</p>

<p>
    Thanks,
</p>
<p><i><?= nl2br(yHtml::encode($emailSignature), false) ?></i></p>
