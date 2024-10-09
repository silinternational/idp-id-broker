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
            $jsonAuthStringKey = sprintf('set%uJsonAuthString', $i);
            $errorsEmailRecipientKey = sprintf('set%uErrorsEmailRecipient', $i);

            if (! array_key_exists($appPrefixKey, $syncSetsParams)) {
                Yii::warning(sprintf(
                    'Finished syncing external groups after %s sync set(s).',
                    ($i - 1)
                ));
                break;
            }

            $appPrefix = $syncSetsParams[$appPrefixKey] ?? '';
            $googleSheetId = $syncSetsParams[$googleSheetIdKey] ?? '';
            $jsonAuthString = $syncSetsParams[$jsonAuthStringKey] ?? '';
            $errorsEmailRecipient = $syncSetsParams[$errorsEmailRecipientKey] ?? '';

            if (empty($appPrefix) || empty($googleSheetId) || empty($jsonAuthString)) {
                Yii::error(sprintf(
                    'Unable to do external-groups sync set %s: app-prefix (%s), '
                    . 'Google Sheet ID (%s), or jsonAuthString (%s) was empty.',
                    $i,
                    json_encode($appPrefix),
                    json_encode($googleSheetId),
                    json_encode($jsonAuthString),
                ));
            } else {
                Yii::warning(sprintf(
                    "Syncing '%s' external groups from Google Sheet (%s)...",
                    $appPrefix,
                    $googleSheetId
                ));
                self::syncSet(
                    $appPrefix,
                    $googleSheetId,
                    $jsonAuthString,
                    $errorsEmailRecipient
                );
            }
        }
    }

    /**
     * Sync the specified external-groups data with the specified Google Sheet.
     *
     * @param string $appPrefix
     * @param string $googleSheetId
     * @param string $jsonAuthString
     * @param string $errorsEmailRecipient
     * @throws \Google\Service\Exception
     */
    public static function syncSet(
        string $appPrefix,
        string $googleSheetId,
        string $jsonAuthString,
        string $errorsEmailRecipient = ''
    ) {
        $desiredExternalGroups = self::getExternalGroupsFromGoogleSheet(
            $googleSheetId,
            $jsonAuthString
        );
        self::processUpdates(
            $appPrefix,
            $desiredExternalGroups,
            $errorsEmailRecipient,
            $googleSheetId
        );
    }

    /**
     * Update users' external-groups using the given data, and handle (and
     * return) any errors.
     *
     * @param string $appPrefix
     * @param array $desiredExternalGroups
     * @param string $errorsEmailRecipient
     * @param string $googleSheetIdForEmail -- The Google Sheet's ID, for use in
     *     the sync-error notification email.
     * @return string[] -- The resulting error messages.
     */
    public static function processUpdates(
        string $appPrefix,
        array $desiredExternalGroups,
        string $errorsEmailRecipient = '',
        string $googleSheetIdForEmail = ''
    ): array {
        $errors = User::updateUsersExternalGroups($appPrefix, $desiredExternalGroups);
        Yii::warning(sprintf(
            "Updated '%s' external groups.",
            $appPrefix
        ));

        if (! empty($errors)) {
            $errorSummary = sprintf(
                'Errors that occurred (%s) while syncing %s external groups: %s',
                count($errors),
                $appPrefix,
                join(" / ", $errors)
            );
            if (strlen($errorSummary) > 1000) {
                $errorSummary = substr($errorSummary, 0, 997) . '...';
            }
            Yii::error($errorSummary);

            if (!empty($errorsEmailRecipient)) {
                $googleSheetUrl = '';
                if (!empty($googleSheetIdForEmail)) {
                    $googleSheetUrl = 'https://docs.google.com/spreadsheets/d/' . $googleSheetIdForEmail;
                }
                self::sendSyncErrorsEmail(
                    $appPrefix,
                    $errors,
                    $errorsEmailRecipient,
                    $googleSheetUrl
                );
            }
        }
        return $errors;
    }

    /**
     * Get the desired external-group values, indexed by email address, from the
     * specified Google Sheet, from the tab named after this IDP's code name
     * (i.e. the name used in this IDP's subdomain).
     *
     * @throws \Google\Service\Exception
     */
    private static function getExternalGroupsFromGoogleSheet(
        string $googleSheetId,
        string $jsonAuthString
    ): array {
        $googleSheetsClient = new Sheets([
            'applicationName' => Yii::$app->params['google']['applicationName'],
            'jsonAuthString' => $jsonAuthString,
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

    /**
     * @param string $appPrefix
     * @param string[] $errors
     * @param string $recipient
     * @param string $googleSheetUrl
     * @return void
     */
    private static function sendSyncErrorsEmail(
        string $appPrefix,
        array $errors,
        string $recipient,
        string $googleSheetUrl
    ) {
        /* @var $emailer Emailer */
        $emailer = \Yii::$app->emailer;
        $emailer->sendExternalGroupSyncErrorsEmail(
            $appPrefix,
            $errors,
            $recipient,
            $googleSheetUrl
        );
    }
}
