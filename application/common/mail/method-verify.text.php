<?php
use yii\helpers\Html as yHtml;

/**
 * @var string $toAddress       Email address to be verified. Generated at runtime.
 * @var string $idpDisplayName  Display name of IDP instance. Default provided by environment variable IDP_DISPLAY_NAME.
 * @var string $code            Verification code. Generated at runtime.
 * @var string $helpCenterUrl   Help center website URL.  Default provided by environment variable HELP_CENTER_URL.
 * @var string $emailSignature  Email signature. Default provided by environment variable EMAIL_SIGNATURE.
 */
?>
Hi there,

    Someone recently requested to add this email address, <?= $toAddress ?>,
    as a method for verifying themselves should they need to reset their
    <?= $idpDisplayName ?> account password. If this was you, you can use the verification code
    below to add it to your account.

    Verification Code: <?= $code ?>

    If you did not request adding this email address to your account please delete this email.

    To keep your account secure, please don't forward this email to anyone.
    See our Help Center for <a href="<?= $helpCenterUrl ?>">more security tips</a>.

    Thanks!
    - <?= $emailSignature ?>

