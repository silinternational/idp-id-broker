<?php

namespace common\components;

use InvalidArgumentException;
use yii\base\Component;
use yii\helpers\Json;

class Sheets extends Component
{
    const FIRST_ROW_AFTER_HEADERS = 2;

    /**
     * @var null|string The Application Name to use with Google_Client.
     */
    public $applicationName = null;

    /**
     * @var null|string The path to the JSON file with authentication
     *     credentials from Google.
     */
    public $jsonAuthFilePath = null;

    /**
     * @var null|string The JSON authentication credentials from Google.
     */
    public $jsonAuthString = null;

    /**
     * @var null|string The Spreadsheet ID.
     */
    public $spreadsheetId = null;

    /**
     * @var array<string> OAuth Scopes needed for reading/writing sheets.
     */
    public $scopes = [\Google_Service_Sheets::SPREADSHEETS];

    /**
     * @var \Google_Service_Sheets
     */
    private $sheets = null;

    /**
     * Init and ensure required properties are set
     */
    public function init()
    {
        if (! empty($this->jsonAuthFilePath)) {
            if (file_exists($this->jsonAuthFilePath)) {
                $this->jsonAuthString = \file_get_contents($this->jsonAuthFilePath);
            } else {
                throw new InvalidArgumentException(sprintf(
                    'JSON auth file path of %s provided, but no such file exists.',
                    var_export($this->jsonAuthFilePath, true)
                ), 1497547815);
            }
        }
        $requiredProperties = [
            'applicationName',
            'jsonAuthString',
            'spreadsheetId',
        ];
        foreach ($requiredProperties as $requiredProperty) {
            if (empty($this->$requiredProperty)) {
                throw new InvalidArgumentException(sprintf(
                    'No %s was provided.',
                    $requiredProperty
                ), 1495648880);
            }
        }

        parent::init();
    }

    protected function getGoogleClient()
    {
        if (! $this->sheets instanceof \Google_Service_Sheets) {
            $jsonCreds = Json::decode($this->jsonAuthString);
            $googleClient = new \Google_Client();
            $googleClient->setApplicationName($this->applicationName);
            $googleClient->setScopes($this->scopes);
            $googleClient->setAuthConfig($jsonCreds);
            $googleClient->setAccessType('offline');
            $googleClient->setSubject('schram@springsgfi.org');
            $this->sheets = new \Google_Service_Sheets($googleClient);
        }

        return $this->sheets;
    }

    public function append(array $records)
    {
        $header = $this->getHeader();

        $table = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($header as $field) {
                $row[] = self::getFieldValue($record, $field);
            }
            $table[] = $row;
        }

        $updateRange = 'Sheet1!A2:ZZ';
        $updateBody = new \Google_Service_Sheets_ValueRange([
            'range' => $updateRange,
            'majorDimension' => 'ROWS',
            'values' => $table,
        ]);

        $client = $this->getGoogleClient();
        $client->spreadsheets_values->append(
            $this->spreadsheetId,
            $updateRange,
            $updateBody,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    public function getHeader()
    {
        $client = $this->getGoogleClient();

        $header = $client->spreadsheets_values->get(
            $this->spreadsheetId,
            'Sheet1!A1:ZZ1',
            ['majorDimension' => 'ROWS']
        );
        return $header['values'][0];
    }

    /**
     * @param string[] $record
     * @param string|null $field
     * @return string
     */
    public static function getFieldValue($record, $field) : string
    {
        $nowAsADateString = date('Y-m-d');
        $nowAsATimeString = date('H:i:s');
        $nowAsADateTimeString = date('Y-m-d H:i:s');

        switch ($field) {
            case 'date':
                return $nowAsADateString;

            case 'time':
                return $nowAsATimeString;

            case 'datetime':
                return $nowAsADateTimeString;

            default:
                $value = $record[$field] ?? null;
                if ($value !== null) {
                    return $value;
                } else {
                    return '';
                }
        }
    }
}
