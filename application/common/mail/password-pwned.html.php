<?php
use yii\helpers\Html as yHtml;

/**
 * @var string $displayName
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
    This email is to inform you that the password you use with your <?= $idpDisplayName ?> identity (IdP) account has
    been discovered in a database of leaked passwords. This does not specifically mean your <?= $idpDisplayName ?>
    identity account has been hacked, it just means you (or someone else) used the same password on other websites as
    well, and one of them was hacked and had its database of credentials stolen.
</p>

<p>
    As a result, your <?= $idpDisplayName ?> identity account password has been disabled for future use and you will need to
    change your password before you can log in again. You should also change the password on any other sites where you
    used the same password. We do not recommend that you use the same password on multiple sites. We HIGHLY recommend
    you use a password manager to generate strong unique passwords for every website.
</p>

<?php
if (!$isMfaEnabled) {
    ?>
    <p>
        We also recommend you enable 2-Step Verification on all accounts where possible, but especially for your
        <?= $idpDisplayName ?> account. With 2-Step Verification, a bad actor having your password would not be able
        to access your account without also having your second authentication factor (a security key, a code generated
        on your phone or computer by an app that does not require an internet connection or a data plan or cell service,
        or your printable backup codes).
    </p>
    <?php
}
?>

<p>
    For more information please visit <a href="<?=$helpCenterUrl?>"><?=$helpCenterUrl?></a> or contact
    <a href="mailto:<?=$supportEmail?>"><?=$supportEmail?></a>.
</p>

<p>Thanks,</p>

<p><i><?= nl2br(yHtml::encode($emailSignature), false) ?></i></p>
