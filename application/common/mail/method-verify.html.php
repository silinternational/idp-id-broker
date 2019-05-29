<?php
use yii\helpers\Html as yHtml;

/**
 * @var string $displayName     Email address to be verified. Provided by user profile.
 * @var string $toAddress       Email address to be verified. Generated at runtime.
 * @var string $idpDisplayName  Display name of IDP instance. Provided by environment variable IDP_DISPLAY_NAME.
 * @var string $uid             Method uid. Generated at runtime.
 * @var string $code            Verification code. Generated at runtime.
 * @var string $supportName     Help center name.  Provided by environment variable SUPPORT_NAME.
 * @var string $supportEmail    Help center email address.  Provided by environment variable SUPPORT_EMAIL.
 * @var string $helpCenterUrl   Help center website URL.  Provided by environment variable HELP_CENTER_URL.
 * @var string $emailSignature  Email signature. Provided by environment variable EMAIL_SIGNATURE.
 * @var string $passwordProfileUrl  URL of password manager. Provided by environment variable PASSWORD_PROFILE_URL.
 */

$verifyLink = $passwordProfileUrl . '/password/recovery/' . $uid . '/verify/' . $code;

?>
<p>Dear <?= yHtml::encode($displayName) ?>,</p>

<p>
    Someone has submitted a request to add this email address, <?= yHtml::encode($toAddress); ?>,
    as a method for verifying themselves should they need to reset their
    <?= yHtml::encode($idpDisplayName); ?> Identity account password. If this was you, please click
    <?= yHtml::a(yHtml::encode($verifyLink), $verifyLink) ?>
    to verify this email address.
</p>
<p>
    If you did not request adding this email address to your account please delete this email
    and contact <?= yHtml::encode($supportName) ?> at <?= yHtml::encode($supportEmail) ?>
    as soon as possible to report the incident.  Do NOT click the link above.
</p>
<p>
    To maintain security, please don't forward this email to anyone.
<?php if (empty($helpCenterUrl)) { ?>
    If you have any questions, please contact <?= yHtml::encode($supportName) ?> at
    <?= yHtml::encode($supportEmail) ?>.
<?php } else { ?>
    See our Help Center at <?= yHtml::a(yHtml::encode($helpCenterUrl), $helpCenterUrl) ?> for more security
    tips.
<?php } ?>
</p>
<p>
    Thanks,
</p>
<p>
    <i><?= nl2br(yHtml::encode($emailSignature), false); ?></i>
</p>
