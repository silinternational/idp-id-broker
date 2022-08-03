<?php
require './vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient as DynamoDbClient;

const ApiKeyTable = "ApiKey";
const ApiKeyValue = "10345678-1234-1234-1234-123456789012";
const WebauthnTable = "WebAuthn";


class DynamoRestart
{

    public DynamoDbClient $client;

    public function init() {
        $this->client = new DynamoDbClient([
            'region'   => getenv('AWS_DEFAULT_REGION'),
            'endpoint' => getenv('AWS_ENDPOINT'),
            'disableSSL' => true,
            'version' => "2012-08-10",
        ]);

    }

    public function createTables()
    {

        $tables = [WebauthnTable => "uuid", ApiKeyTable => "value"];

        print_r(PHP_EOL . "Deleting old dynamodb tables." . PHP_EOL);
        foreach ($tables as $table => $type) {
            try {
                $this->client->deleteTable(['TableName' => $table]);
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), "400 Bad Request")) {
                    throw $e;
                }
            }
        }

        print_r("Creating dynamodb tables." . PHP_EOL);

        $this->client->createTable([
            'TableName' => WebauthnTable,
            'KeySchema' => [['AttributeName' => 'uuid', 'KeyType' => 'HASH']],
            'AttributeDefinitions' => [['AttributeName' => 'uuid', 'AttributeType' => 'S']],
            'ProvisionedThroughput' => ['ReadCapacityUnits' => 10, 'WriteCapacityUnits' => 10],
        ]);

        $this->client->createTable([
            'TableName' => ApiKeyTable,
            'KeySchema' => [['AttributeName' => 'value', 'KeyType' => 'HASH']],
            'AttributeDefinitions' => [['AttributeName' => 'value', 'AttributeType' => 'S']],
            'ProvisionedThroughput' => ['ReadCapacityUnits' => 10, 'WriteCapacityUnits' => 10],
        ]);

        $this->client->waitUntil('TableExists', array(
            'TableName' => ApiKeyTable
        ));
        print_r("Finished creating dynamodb tables." . PHP_EOL);
    }

    public function initApiKeys()
    {
        print_r("Creating api key(s)." . PHP_EOL);

        $this->client->putItem([
            'Item' => [
                'value' => [
                    'S' => ApiKeyValue,
                ],
                'hashedApiSecret' => [
                    'S' => '$2a$10$8Bp9PqqfStjLvh1nQJ67JeY3CO/mEXmF1GKfe8Vk0kue1.i7fa2mC',
                ],
                'email' => [
                    'S' => 'example-user@example.com',
                ],
                'activatedAt' => [
                    'N' => '1590518080000',
                ],
                'createdAt' => [
                    'N' => '1590518080000',
                ],
            ],
            'TableName' => ApiKeyTable,
        ]);
    }

    public function initWebauthnEntries()
    {
        print_r("Creating WebauthnEntries." . PHP_EOL);

        $this->client->putItem([
            'Item' => [
                'uuid' => [
                    'S' => '097791bf-2385-4ab4-8b06-14561a338d8e',
                ],
                'apiKey' => [
                    'S' => ApiKeyValue,
                ],
                'encryptedAppId' => [
                    'S' => 'SomeEncryptedAppId',
                ],
                'encryptedKeyHandle' => [
                    'S' => 'SomeEncryptedKeyHandle',
                ],
//                'encryptedCredentials' => [
//                    'B' => '0x11',
//                ],
            ],
            'TableName' => WebauthnTable,
        ]);

        print_r("Finished creating WebauthnEntries." . PHP_EOL);
    }

    public function verifyData() {
        $result = $this->client->getItem([
            'ConsistentRead' => true,
            'TableName' => ApiKeyTable,
            'Key'       => ['value'   => ['S' => ApiKeyValue]],
        ]);

        if (empty($result['Item']['value']['S'])) {
            throw new \Exception("Api Key data appears not to have been created", 1508004000);
        }
        echo("Data is present in api key table" . PHP_EOL . var_export($result['Item'], true) . PHP_EOL);
    }
}