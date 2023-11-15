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
 * @var array  $mfaOptions
 */
?>
<p>Dear <?= yHtml::encode($displayName) ?>,</p>

<p>
    You have added a new option for 2-Step Verification to your <?= yHtml::encode($idpDisplayName) ?> Identity account.
    When you are prompted for 2-Step Verification in the future, you will have the option to choose a different
    method of 2-Step Verification if you do not have access to the primary method.
</p>
<p>
    You now have the following 2-Step Verification options enabled:
</p>
<ol>
    <?php
        foreach ($mfaOptions as $mfa) {
            echo '<li>' . yHtml::encode($mfa->getReadableType()) . '</li>';
        }
?>
</ol>

<p>
    If you have any questions or concerns about this, please contact <?= yHtml::encode($supportName) ?> at
    <?= yHtml::encode($supportEmail) ?>.
</p>
<p>
    Thanks,
</p>
<p><i><?= nl2br(yHtml::encode($emailSignature), false) ?></i></p>
