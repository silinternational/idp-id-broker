<?php

namespace common\components;

use common\models\User;
use Webmozart\Assert\Assert;
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
                    "Syncing '%s' external groups from Google Sheet (%s)...",
                    $appPrefix,
                    $googleSheetId
                ));
                self::syncSet($appPrefix, $googleSheetId);
            }
        }
    }

    private static function syncSet(string $appPrefix, string $googleSheetId)
    {
        $desiredExternalGroups = self::getExternalGroupsFromGoogleSheet($googleSheetId);
        $errors = User::updateUsersExternalGroups($appPrefix, $desiredExternalGroups);
        Yii::warning(sprintf(
            "Ran sync for '%s' external groups.",
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

    /**
     * Get the desired external-group values, indexed by email address, from the
     * specified Google Sheet, from the tab named after this IDP's code name
     * (i.e. the name used in this IDP's subdomain).
     *
     * @throws \Google\Service\Exception
     */
    private static function getExternalGroupsFromGoogleSheet(string $googleSheetId): array
    {
        $googleSheetsClient = new Sheets([
            'applicationName' => Yii::$app->params['google']['applicationName'],
            'jsonAuthFilePath' => Yii::$app->params['google']['jsonAuthFilePath'],
            'jsonAuthString' => Yii::$app->params['google']['jsonAuthString'],
            'spreadsheetId' => $googleSheetId,
        ]);
        $tabName = Yii::$app->params['idpName'];

        $values = $googleSheetsClient->getValuesFromTab($tabName);
        $columnLabels = $values[0];

        Assert::eq($columnLabels[0], 'email', sprintf(
            "The first column in the '%s' tab must be 'email'",
            $tabName
        ));
        Assert::eq($columnLabels[1], 'groups', sprintf(
            "The second column in the '%s' tab must be 'groups'",
            $tabName
        ));
        Assert::eq(
            count($columnLabels),
            2,
            'There should only be two columns with values'
        );

        $data = [];
        for ($i = 1; $i < count($values); $i++) {
            $email = trim($values[$i][0]);
            $groups = trim($values[$i][1] ?? '');
            $data[$email] = $groups;
        }
        return $data;
    }
}
