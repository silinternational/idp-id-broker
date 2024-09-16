<?php

namespace common\components;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;
use yii\helpers\Json;

class MfaApiClient
{
    /**
     * @var string
     */
    public string $apiBaseUrl;

    /**
     * @var string
     */
    public string $apiKey;

    /**
     * @var string
     */
    public string $apiSecret;

    /**
     * @var GuzzleClient
     */
    public GuzzleClient $client;

    /**
     * Associative array of headers to include with each request
     * @var array
     */
    private array $headers;

    public function __construct(string $apiBaseUrl, $apiKey, $apiSecret)
    {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('Missing MFA configuration for api key');
        }

        if (empty($apiSecret)) {
            throw new \InvalidArgumentException('Missing MFA configuration for api secret');
        }

        if (substr($apiBaseUrl, -1) !== '/') {
            throw new \InvalidArgumentException('The MFA apiBaseUrl must end with a slash (/).');
        }
        $this->headers = [
            'X-MFA-APIKey' => $apiKey,
            'X-MFA-APISecret' => $apiSecret,
            'Content-type' => 'application/json',
            'User-Agent' => 'idp-id-broker',
        ];

        $this->client = new GuzzleClient([
            'base_uri' => $apiBaseUrl,
            'timeout' => 30,
            'headers' => $this->headers,
        ]);
    }

    /**
     * Create a new TOTP configuration
     * @param string $username
     * @return array
     * @throws GuzzleException
     */
    public function createTotp(string $username, string $issuer): array
    {
        $response = $this->callApi('totp', 'POST', [
            'issuer' => $issuer,
            'label' => $username,
        ]);

        return Json::decode($response->getBody()->getContents());
    }

    /**
     * Delete an existing TOTP configuration
     * @param string $uuid
     * @return bool
     * @throws GuzzleException
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
     * @throws GuzzleException
     */
    public function validateTotp(string $uuid, string $code): bool
    {
        try {
            $this->callApi('totp/' . $uuid . '/validate', 'POST', [
                'code' => $code,
            ]);
            return true;
        } catch (ClientException $e) {
            $errorCode = $e->getCode();
            if (($errorCode === 400) || ($errorCode === 401)) {
                // 400 = no code provided
                // 401 = invalid code provided
                return false;
            }
            throw $e;
        }
    }


    /**
     * @param array $additionalHeaders
     * @return array
     * @throws GuzzleException
     */
    public function webauthnCreateAuthentication(array $additionalHeaders): array
    {
        $response = $this->callApi('webauthn/login', 'POST', [], $additionalHeaders);
        return Json::decode($response->getBody()->getContents());
    }

    /**
     * @param array $additionalHeaders
     * @param array $signResultJson
     * @return array
     * @throws GuzzleException
     */
    public function webauthnValidateAuthentication(array $additionalHeaders, array $signResultJson): array
    {
        $response = $this->callApi('webauthn/login', 'PUT', $signResultJson, $additionalHeaders);
        return Json::decode($response->getBody()->getContents());
    }

    /**
     * @param array $additionalHeaders
     * @return array
     * @throws GuzzleException
     */
    public function webauthnCreateRegistration(array $additionalHeaders): array
    {
        $response = $this->callApi('webauthn/register', 'POST', [], $additionalHeaders);
        return Json::decode($response->getBody()->getContents());
    }

    /**
     * @param array $additionalHeaders
     * @param array $signResultJson
     * @return array
     * @throws GuzzleException
     */
    public function webauthnValidateRegistration(array $additionalHeaders, array $signResultJson): array
    {
        $response = $this->callApi('webauthn/register', 'PUT', $signResultJson, $additionalHeaders);
        return Json::decode($response->getBody()->getContents());
    }

    /**
     * @param array $additionalHeaders
     * @return bool
     * @throws GuzzleException
     */
    public function webauthnDelete(array $additionalHeaders): bool
    {
        $this->callApi('webauthn/user', 'DELETE', [], $additionalHeaders);
        return true;
    }

    /**
     * Delete one of a user's u2f/webauthn credentials
     * @param string $credId Credential ID
     * @param array $additionalHeaders
     * @return bool
     * @throws GuzzleException
     */
    public function webauthnDeleteCredential(string $credId, array $additionalHeaders): bool
    {
        $this->callApi("webauthn/credential/$credId", 'DELETE', [], $additionalHeaders);
        return true;
    }

    /**
     * @param string $path
     * @param string $method
     * @param array $body
     * @param array $additionalHeaders
     * @return mixed|ResponseInterface
     * @throws GuzzleException
     */
    private function callApi(string $path, string $method, array $body = [], array $additionalHeaders = [])
    {
        try {
            return $this->client->request(
                $method,
                $path,
                [
                    'json' => $body,
                    'headers' => array_merge($this->headers, $additionalHeaders)
                ]
            );
        } catch (\Exception $e) {
            if ($e instanceof ConnectException || $e instanceof ServerException) {
                \Yii::error([
                    'action' => 'calling 2sv api',
                    'status' => 'error',
                    'error' => 'connection error: ' . $e->getMessage()
                ]);
            }

            throw $e;
        }
    }


}
