<?php

use yii\helpers\Html as yHtml;

/**
 * @var string $displayName     Name of user. Provided by user profile.
 * @var string $recoveryEmail   Email address for recovery/manager contact. Provided by user profile or the MFA request.
 * @var string $idpDisplayName  Display name of IDP instance. Provided by environment variable IDP_DISPLAY_NAME.
 * @var string $supportName     Help center name.  Provided by environment variable SUPPORT_NAME.
 * @var string $supportEmail    Help center email address.  Provided by environment variable SUPPORT_EMAIL.
 * @var string $emailSignature  Email signature. Provided by environment variable EMAIL_SIGNATURE.
 */
?>
<p>Dear <?= yHtml::encode($displayName) ?>,</p>

<p>
    You have requested assistance in accessing your <?= yHtml::encode($idpDisplayName) ?> Identity account. An email
    containing an access code has been sent to your recovery contact at <?= yHtml::encode($recoveryEmail) ?>.
    This access code can be used in place of your other 2-Step Verification options. Please contact
    your recovery contact to obtain this access code from them.
</p>
<p>
    If you did not request this code, it could be a sign someone else has compromised your account. Please
    contact <?= yHtml::encode($supportName) ?> at <?= yHtml::encode($supportEmail) ?>
    as soon as possible to report the incident.
</p>
<p>
    Thanks,
</p>
<p>
    <i><?= nl2br(yHtml::encode($emailSignature), false); ?></i>
</p>