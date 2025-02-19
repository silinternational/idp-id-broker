<?php
use Sil\PhpEnv\Env;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
</head>
<body>
    <?php
    $brandColor = Env::get('EMAIL_BRAND_COLOR', '');
$logo = Env::get('EMAIL_BRAND_LOGO', '');
$maxWidth = Env::get('EMAIL_MAX_WIDTH', '600px');
?>
    <div style="padding: 25px 15px;">
        <div style="margin-left: auto; margin-right: auto; max-width: <?= $maxWidth ?>;">
            <header>
                <table style="background-color: <?= $brandColor ?>; width: 100%">
                    <tr>
                        <td>
                            <img src="<?= $logo ?>" style="max-height: 4em; vertical-align: middle">
                        </td>
                    </tr>
                </table>
            </header>

            <div style="border-bottom: 1px solid #d3d3d3; padding: 10px;">
                <?php
            /* @var $content string email contents */
?>
                <?= $content ?>
            </div>
        </div>
    </div>
</body>
</html>
