<?php
use yii\helpers\Html as yHtml;

/**
 * @var string $displayName     Name of user. Provided by user profile.
 * @var string $firstName       First name of user. Provided by user profile.
 * @var string $idpDisplayName  Display name of IDP instance. Provided by environment variable IDP_DISPLAY_NAME.
 * @var string $code            Rescue code. Generated at runtime.
 * @var string $supportName     Help center name.  Provided by environment variable SUPPORT_NAME.
 * @var string $supportEmail    Help center email address.  Provided by environment variable SUPPORT_EMAIL.
 * @var string $helpCenterUrl   Help center website URL.  Provided by environment variable HELP_CENTER_URL.
 * @var string $emailSignature  Email signature. Provided by environment variable EMAIL_SIGNATURE.
 */
?>
<p>Hello,</p>

<p>
    <?= yHtml::encode($displayName) ?> has requested your assistance in accessing their
    <?= yHtml::encode($idpDisplayName) ?> Identity account. This email contains a backup code they need
    to proceed with login. Please contact them directly to ensure that you are only providing
    the following backup code to them and not to someone else.
</p>
<p>
    Backup Code: <?= yHtml::encode($code) ?>
</p>
<p>
    If <?= yHtml::encode($firstName) ?> did not do this, it could be a sign someone else has compromised
    their account. Please contact <?= yHtml::encode($supportName) ?> at <?= yHtml::encode($supportEmail) ?>
    as soon as possible to report the incident.
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
