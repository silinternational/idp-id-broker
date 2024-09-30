<?php
use yii\helpers\Html as yHtml;

/**
 * @var string $appPrefix
 * @var string[] $errors
 * @var string $googleSheetUrl
 * @var string $emailSignature
 * @var string $idpDisplayName
 * @var string $idpName
 */
?>
<p>
    The following errors occurred when syncing the <?= yHtml::encode($appPrefix) ?>
    external groups to the <?= yHtml::encode($idpDisplayName) ?> IDP:
</p>
<?= yHtml::ul($errors) ?>

<?php
if (empty($googleSheetUrl)) {
    ?>
    <p>
        If any of these seem like simple data problems, you can potentially fix them
        by updating the information in the Google Sheet used for this
        external-groups sync.
    </p>
    <?php
} else {
    ?>
    <p>
        If any of these seem like simple data problems, you can potentially fix them
        by updating the information in the "<?= yHtml::encode($idpName) ?>" tab of
        this Google Sheet: <br />
        <?= yHtml::a($googleSheetUrl, $googleSheetUrl) ?>
    </p>
    <?php
}
?>

<p>
    Other users' external groups may have been synced successfully.
</p>

<p><i><?= nl2br(yHtml::encode($emailSignature), false) ?></i></p>
