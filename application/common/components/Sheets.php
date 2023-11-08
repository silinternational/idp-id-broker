<?php

namespace common\components;

use InvalidArgumentException;
use yii\base\Component;
use yii\helpers\Json;

class Sheets extends Component
{
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
     * @var null|string The delegated admin account for Google API access.
     */
    public $delegatedAdmin = null;

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
    private $service = null;

    /**
     * Init and ensure required properties are set
     */
    public function init()
    {
        $this->applicationName = \Yii::$app->params['google']['applicationName'];
        $this->jsonAuthFilePath = \Yii::$app->params['google']['jsonAuthFilePath'];
        $this->jsonAuthString = \Yii::$app->params['google']['jsonAuthString'];
        $this->delegatedAdmin = \Yii::$app->params['google']['delegatedAdmin'];
        $this->spreadsheetId = \Yii::$app->params['google']['spreadsheetId'];

        if (!empty($this->jsonAuthFilePath)) {
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
        if (!$this->service instanceof \Google_Service_Sheets) {
            $jsonCreds = Json::decode($this->jsonAuthString);
            $googleClient = new \Google_Client();
            $googleClient->setApplicationName($this->applicationName);
            $googleClient->setScopes($this->scopes);
            $googleClient->setAuthConfig($jsonCreds);
            $googleClient->setAccessType('offline');
            if (!empty($this->delegatedAdmin)) {
                $googleClient->setSubject($this->delegatedAdmin);
            }
            $this->service = new \Google_Service_Sheets($googleClient);
        }

        return $this->service;
    }

    public function append(array $records)
    {
        $header = $this->getHeader();
        $table = self::makeTable($header, $records);

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
     * @param string[] $header
     * @param array $records
     * @return array
     */
    public static function makeTable(array $header, array $records): array
    {
        $nowAsADateString = date('Y-m-d');
        $nowAsATimeString = date('H:i:s');
        $nowAsADateTimeString = date('Y-m-d H:i:s');

        $table = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($header as $field) {
                switch ($field) {
                    case 'date':
                        $row[] = $nowAsADateString;
                        break;

                    case 'time':
                        $row[] = $nowAsATimeString;
                        break;

                    case 'datetime':
                        $row[] = $nowAsADateTimeString;
                        break;

                    default:
                        $value = $record[$field] ?? null;
                        if ($value !== null) {
                            $row[] = $value;
                        } else {
                            $row[] = '';
                        }
                        break;
                }
            }
            $table[] = $row;
        }
        return $table;
    }
}
