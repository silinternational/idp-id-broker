<?php

use Behat\Testwork\ServiceContainer\Exception\ConfigurationLoadingException;
use common\helpers\Utils;
use common\models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Sil\SilIdBroker\Behat\Context\YiiContext;
use Webmozart\Assert\Assert;

/**
 * Defines application features from the specific context.
 */
class UserContext extends YiiContext
{
    private $reqHeaders = [];
    private $reqBody = [];

    /** @var Response */
    private $response;
    private $resBody = [];

    /** @var User */
    private $userFromDb;

    private $now;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @Given /^a user "([^"]*)" with "([^"]*)" of "([^"]*)"$/
     */
    public function aUserWithOf($existsOrNot, $propertyName, $propertyValue)
    {
        $user = User::findOne([
            $propertyName => $propertyValue
        ]);

        ($existsOrNot === 'exists') ? Assert::notNull($user)
                                    : Assert::null($user);
    }

    /**
     * @Given /^the requester "([^"]*)" authorized$/
     */
    public function theRequesterAuthorized($isOrIsNot)
    {
        if ($isOrIsNot === 'is') {
            $key = $this->getEnv('API_ACCESS_KEY');

            $this->reqHeaders['Authorization'] = "Bearer $key";
        }
    }

    private function getEnv($key): string
    {
        $value = getenv($key);

        if (empty($value)) {
            throw new ConfigurationLoadingException("$key missing from environment.");
        }

        return $value;
    }

    /**
     * @When /^I provide a valid "([^"]*)" of "([^"]*)"$/
     */
    public function iProvideAValidOf($propertyName, $propertyValue)
    {
        $this->reqBody[$propertyName] = $propertyValue;
    }

    /**
     * @Given /^I request the user be created with an "([^"]*)" of "([^"]*)"$/
     */
    public function iRequestTheUserBeCreatedWithAnOf($keyName, $keyValue)
    {
        $this->reqBody[$keyName] = $keyValue;

        $client = $this->buildClient();

        $this->response = $client->post('/user');

        $this->now = Utils::now();

        $this->resBody = $this->extractBody($this->response);

        $this->userFromDb = User::findOne([
            $keyName => $keyValue
        ]);
    }

    private function buildClient(): Client
    {
        $hostname = $this->getEnv('TEST_SERVER_HOSTNAME');

        return new Client([
            'base_uri' => "http://$hostname",
            'headers' => $this->reqHeaders,
            'json' => $this->reqBody,
        ]);
    }

    private function extractBody(Response $response): array
    {
        $jsonBlob = $response->getBody()->getContents();

        return json_decode($jsonBlob, true);
    }

    /**
     * @Then /^the response status code should be "([^"]*)"$/
     */
    public function theResponseStatusCodeShouldBe($statusCode)
    {
        Assert::eq($this->response->getStatusCode(), $statusCode);
    }

    /**
     * @Then /^"([^"]*)" should be returned as "([^"]*)"$/
     */
    public function shouldBeReturnedAs($propertyName, $propertyValue)
    {
        Assert::eq($this->resBody[$propertyName], $propertyValue);
    }

    /**
     * @Given /^"([^"]*)" should not be returned$/
     */
    public function shouldNotBeReturned($propertyName)
    {
        Assert::keyNotExists($this->resBody, $propertyName);
    }

    /**
     * @Given /^"([^"]*)" should be returned as now UTC$/
     */
    public function shouldBeReturnedAsNowUTC($propertyName)
    {
        Assert::eq($this->resBody[$propertyName], $this->now);
    }

    /**
     * @Given /^"([^"]*)" should be stored as "([^"]*)"$/
     */
    public function shouldBeStoredAs($propertyName, $propertyValue)
    {
        Assert::eq($this->userFromDb->$propertyName, $propertyValue);
    }

    /**
     * @Given /^"([^"]*)" should be stored as null$/
     */
    public function shouldBeStoredAsNull($propertyName)
    {
        Assert::null($this->userFromDb->$propertyName);
    }

    /**
     * @Given /^"([^"]*)" should be stored as now UTC$/
     */
    public function shouldBeStoredAsNowUTC($propertyName)
    {
        Assert::eq($this->userFromDb->$propertyName, $this->now);
    }
}
