<?php
namespace common\components;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use yii\helpers\Json;

class MfaApiClient
{
    public $apiBaseUrl;

    public $apiKey;

    public $apiSecret;

    public $client;

    public function __construct(string $apiBaseUrl, $apiKey, $apiSecret)
    {
        $this->client = new GuzzleClient([
            'base_uri' => $apiBaseUrl,
            'timeout' => 5,
            'headers' => [
                'X-TOTP-APIKey' => $apiKey,
                'X-TOTP-APISecret' => $apiSecret,
                'Content-type' => 'application/json',
            ],
        ]);
    }

    /**
     * Create a new TOTP configuration
     * @param string $username
     * @return array
     */
    public function createTotp(string $username): array
    {
        $response = $this->callApi('totp', 'POST', [
            'issuer' => \Yii::$app->params['idpDisplayName'],
            'label' => $username,
        ]);

        return Json::decode($response->getBody()->getContents());
    }

    /**
     * Delete an existing TOTP configuration
     * @param string $uuid
     * @return bool
     */
    public function deleteTotp(string $uuid): bool
    {
        $this->callApi('totp/' . $uuid, 'DELETE');
        return true;
    }

    /**
     * Validate given value for TOTP configuration
     * @param string $uuid
     * @param string $code
     * @return bool
     */
    public function validateTotp(string $uuid, string $code): bool
    {
        try {
            $this->callApi('totp/' . $uuid . '/validate', 'POST', [
                'code' => $code,
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $path
     * @param string $method
     * @param array $body
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    private function callApi(string $path, string $method, array $body = [])
    {
        try {
            return $this->client->request(
                $method,
                $path,
                ['json' => $body]
            );
        } catch (\Exception $e) {
            if ($e instanceof ConnectException || $e instanceof ServerException) {
                \Yii::error([
                    'action' => 'calling totp api',
                    'status' => 'error',
                    'error' => 'connection error: ' . $e->getMessage()
                ]);
            }

            throw $e;
        }
    }


}