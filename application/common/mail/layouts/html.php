<?php

use Sil\PhpEnv\Env;

$brandColor = Env::get('EMAIL_BRAND_COLOR', '');
$logo = Env::get('EMAIL_BRAND_LOGO', '');
$maxWidth = Env::get('EMAIL_MAX_WIDTH', '600px');
/* @var $content string email contents */

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
  <title>Message</title>
</head>
<body>
    <div style="padding: 25px 15px;">
        <div style="margin-left: auto; margin-right: auto; max-width: <?= $maxWidth ?>;">
            <header>
                <table style="background-color: <?= $brandColor ?>; width: 100%">
                    <tr>
                        <th style="text-align: left;">
                            <img
                              src="<?= $logo ?>"
                              style="max-height: 4em; vertical-align: middle;"
                              alt="logo"
                            >
                        </th>
                    </tr>
                </table>
            </header>

            <div style="border-bottom: 1px solid #d3d3d3; padding: 10px;">
                <?= $content ?>
            </div>
        </div>
    </div>
</body>
</html>
