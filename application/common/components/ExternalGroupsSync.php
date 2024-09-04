<?php

namespace common\components;

use common\models\User;
use Yii;
use yii\base\Component;

class ExternalGroupsSync extends Component
{
    public const MAX_SYNC_SETS = 20;

    public static function syncAllSets(array $syncSetsParams)
    {
        for ($i = 1; $i <= self::MAX_SYNC_SETS; $i++) {
            $appPrefixKey = sprintf('set%uAppPrefix', $i);
            $googleSheetIdKey = sprintf('set%uGoogleSheetId', $i);

            if (! array_key_exists($appPrefixKey, $syncSetsParams)) {
                Yii::warning(sprintf(
                    'Finished syncing external groups after %s sync set(s).',
                    ($i - 1)
                ));
                break;
            }

            $appPrefix = $syncSetsParams[$appPrefixKey] ?? null;
            $googleSheetId = $syncSetsParams[$googleSheetIdKey] ?? null;

            if (empty($appPrefix) || empty($googleSheetId)) {
                Yii::error(sprintf(
                    'Unable to do external-groups sync set %s: '
                    . 'app-prefix (%s) or Google Sheet ID (%s) was empty.',
                    $i,
                    json_encode($appPrefix),
                    json_encode($googleSheetId),
                ));
            } else {
                Yii::warning(sprintf(
                    'Syncing %s external groups from Google Sheet (%s)...',
                    json_encode($appPrefix),
                    $googleSheetId
                ));
                self::syncSet($appPrefix, $googleSheetId);
            }
        }
    }

    private static function syncSet(string $appPrefix, string $googleSheetId)
    {
        $desiredExternalGroups = self::getExternalGroupsFromGoogleSheet($googleSheetId);
        $errors = User::syncExternalGroups($appPrefix, $desiredExternalGroups);
        Yii::warning(sprintf(
            'Ran sync for %s external groups.',
            $appPrefix
        ));

        if (! empty($errors)) {
            Yii::error(sprintf(
                'Errors that occurred while syncing %s external groups: %s',
                $appPrefix,
                join(" / ", $errors)
            ));
        }
    }
}
