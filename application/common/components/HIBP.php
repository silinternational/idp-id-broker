<?php
namespace common\components;

use GuzzleHttp\Client;

class HIBP
{
    const HIBPBaseURL = "https://api.pwnedpasswords.com/range/";


    /**
     * @param string $password
     * @return bool
     * @throws \Exception
     */
    public static function isPwned(string $password): bool {
        $hashes = self::asHashes($password);

        $client = new Client([
            'base_uri' => self::HIBPBaseURL,
            'timeout' => 2,
        ]);
        $response = $client->get($hashes['Prefix']);
        if ($response->getStatusCode() != 200) {
            throw new \Exception(
                "Error calling HIBP service, status code: " . $response->getStatusCode(), 1586375152);
        }

        $results = $response->getBody()->getContents();

        // remove found counts for easier matching
        $sufixes = preg_replace('/:\d+/m','', $results);
        $hashlist = explode('\n', $sufixes);

        return in_array($hashes['Suffix'], $hashlist);
    }

    private static function asHashes(string $password): array {
        $hash = sha1($password);
        return [
            "Prefix" => substr($hash, 0,4),
            "Suffix" => substr($hash, 5,strlen($hash)-1),
        ];
    }


}