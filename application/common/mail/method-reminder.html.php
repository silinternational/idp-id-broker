<?php
use yii\helpers\Html as yHtml;

/**
 * @var string $displayName        Email address to be verified. Provided by user profile.
 * @var string $alternateAddress   Email address to be verified. Generated at runtime.
 * @var string $idpDisplayName     Display name of IDP instance. Provided by environment variable IDP_DISPLAY_NAME.
 * @var string $supportName        Help center name.  Provided by environment variable SUPPORT_NAME.
 * @var string $supportEmail       Help center email address.  Provided by environment variable SUPPORT_EMAIL.
 * @var string $helpCenterUrl      Help center website URL.  Provided by environment variable HELP_CENTER_URL.
 * @var string $emailSignature     Email signature. Provided by environment variable EMAIL_SIGNATURE.
 * @var string $passwordProfileUrl URL of password manager. Provided by environment variable PASSWORD_PROFILE_URL.
 */

?>
<p>Dear <?= yHtml::encode($displayName) ?>,</p>

<p>
    You recently requested to add an email address, <?= yHtml::encode($alternateAddress); ?>,
    as an alternate method for verifying your Identity should you ever need to reset your
    <?= yHtml::encode($idpDisplayName); ?> Identity account password. Please open the inbox for
    that account and click the link in the verification email we sent earlier. If you cannot
    find that email, you can generate a new one at your profile page here:
    <?= yHtml::a(yHtml::encode($passwordProfileUrl), $passwordProfileUrl) ?>
</p>
<p>
<?php if (empty($helpCenterUrl)) { ?>
    If you have any questions, please contact <?= yHtml::encode($supportName) ?> at
    <?= yHtml::encode($supportEmail) ?>.
<?php } else { ?>
    If you have any questions, you can visit our Help Center at
    <?= yHtml::a(yHtml::encode($helpCenterUrl), $helpCenterUrl) ?>
<?php } ?>
</p>
<p>
    Thanks,
</p>
<p>
    <i><?= nl2br(yHtml::encode($emailSignature), false); ?></i>
</p>
