<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\ServiceContainer\Exception\ConfigurationLoadingException;
use common\helpers\Utils;
use common\models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Sil\SilIdBroker\Behat\Context\YiiContext;
use Webmozart\Assert\Assert;

class UserContext extends YiiContext
{
    private $reqHeaders = [];
    private $reqBody = [];

    /** @var Response */
    private $response;
    private $resBody = [];

    /** @var User */
    private $userFromDb;
    /** @var User */
    private $userFromDbBefore;

    private $now;

    /**
     * @Given the requester is not authorized
     */
    public function theRequesterIsNotAuthorized()
    {
        unset($this->reqHeaders['Authorization']);
    }

    /**
     * @Given there are no users yet
     */
    public function theUserStoreIsEmpty()
    {
        User::deleteAll();
    }

    /**
     * @When /^I request the user be (created|updated|deleted|retrieved|<.*>)$/
     */
    public function iRequestTheUserBe($action)
    {
        $client = $this->buildClient();

        $this->response = $this->sendRequest($client, $action, '/user');

        $this->now = Utils::now();

        $this->resBody = $this->extractBody($this->response);
    }

    private function buildClient(): Client
    {
        $hostname = $this->getEnv('TEST_SERVER_HOSTNAME');

        return new Client([
            'base_uri' => "http://$hostname",
            'http_errors' => false, // don't throw exceptions on 4xx/5xx so responses can be inspected.
            'headers' => $this->reqHeaders,
            'json' => $this->reqBody,
        ]);
    }

    private function sendRequest(Client $client, string $action, string $resource): ResponseInterface
    {
        switch ($action) {
            case 'created': return $client->post  ($resource);
            case 'updated': return $client->put   ($resource);
            case 'deleted': return $client->delete($resource);
                   default: return $client->get   ($resource);
        }
    }

    private function extractBody(Response $response): array
    {
        $jsonBlob = $response->getBody()->getContents();

        return json_decode($jsonBlob, true);
    }

    /**
     * @Then the response status code should be :statusCode
     */
    public function theResponseStatusCodeShouldBe($statusCode)
    {
        Assert::eq($this->response->getStatusCode(), $statusCode);
    }

    /**
     * @Then /^the property (\w+) should contain "(.*)"/
     */
    public function thePropertyShouldContain($property, $contents)
    {
        Assert::contains($this->resBody[$property], $contents);
    }

    /**
     * @Then a user was not created
     */
    public function aUserWasNotCreated()
    {
        Assert::isEmpty(User::find()->all());
    }

    /**
     * @Given the requester is authorized
     */
    public function theRequesterIsAuthorized()
    {
        $key = $this->getEnv('API_ACCESS_KEY');

        $this->reqHeaders['Authorization'] = "Bearer $key";
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
     * @Given I provide all the required fields
     */
    public function iProvideAllTheRequiredFields()
    {
        $this->reqBody = [
            'employee_id' => '123',
            'first_name' => 'Shep',
            'last_name' => 'Clark',
            'username' => 'shep_clark',
            'email' => 'shep_clark@example.org',
        ];
    }

    /**
     * @Given /^I do not provide (?:a|an) (.*)$/
     */
    public function iDoNotProvideA($property)
    {
        unset($this->reqBody[$property]);
    }

    /**
     * @Given /^I provide an invalid (.*) of "?([^"]*)"?$/
     */
    public function iProvideAnInvalidPropertyValue($property, $value)
    {
        $this->reqBody[$property] = $value;
    }

    /**
     * @Transform /^(true|false)$/
     */
    public function transformBool($string)
    {
        return boolval($string);
    }

    /**
     * @Transform /^null$/
     */
    public function transformNull()
    {
        return null;
    }

    /**
     * @Given /^I provide (?:a|an) (.*) that is too long$/
     */
    public function iProvideAPropertyThatIsTooLong($property)
    {
        $this->reqBody[$property] = str_repeat("z", 256);
    }

    /**
     * @Given /^a user does not exist with (?:a|an) (.*) of "?([^"]*)"?$/
     */
    public function aUserDoesNotExist($property, $value)
    {
         User::deleteAll([$property => $value]);
    }

    /**
     * @Given I provide the following valid data:
     */
    public function iProvideTheFollowingValidData(TableNode $data)
    {
        foreach ($data as $row) {
            $this->reqBody[$row['property']] = $row['value'];
        }
    }

    /**
     * @Given /^the following data (is|is not) returned:$/
     */
    public function theFollowingDataReturned($isOrIsNot, TableNode $data)
    {
        foreach ($data as $row) {
            $isOrIsNot === 'is' ? Assert::eq($this->resBody[$row['property']], $row['value'])
                                : Assert::keyNotExists($this->resBody, $row['property']);
        }
    }

    /**
     * @Then :property should be returned as now UTC
     */
    public function shouldBeReturnedAsNowUTC($property)
    {
        Assert::eq($this->resBody[$property], $this->now);
    }


    /**
     * @Then /^a user exists with (?:a|an) (.*) of "?([^"]*)"?$/
     */
    public function aUserExistsForThisKey($lookupKey, $lookupValue)
    {
        $this->userFromDb = User::findOne([$lookupKey => $lookupValue]);

        Assert::notNull($this->userFromDb);
    }

    /**
     * @Then the following data should be stored:
     */
    public function theFollowingDataIsStored(TableNode $data)
    {
        foreach ($data as $row) {
            $property = $row['property'];
            $expectedValue = $row['value'];

            Assert::eq($this->userFromDb->$property, $this->transformNULLs($expectedValue));
        }
    }

    //TODO: remove once https://github.com/Behat/Behat/issues/777 is resolved for tables.
    private function transformNULLs($value)
    {
        return ($value === "NULL") ? NULL : $value;
    }

    /**
     * @Then :property should be stored as now UTC
     */
    public function shouldBeStoredAsNowUTC($property)
    {
        Assert::eq($this->userFromDb->$property, $this->now);
    }

    /**
     * @When I request the user be created again
     */
    public function iRequestTheUserBeCreatedAgain()
    {
        $this->userFromDbBefore = $this->userFromDb;

        sleep(1); // so timestamps won't be the same

        $this->iRequestTheUserBe('created');
    }

    /**
     * @Then the only property to change should be :property
     */
    public function theOnlyPropertyToChangeShouldBe($property)
    {
        foreach ($this->userFromDbBefore->attributes as $name => $value) {
            $previous = $this->userFromDbBefore->$name;
            $current = $this->userFromDb->$name;

            if ($name === $property) {
                Assert::notEq($current, $previous);
            } else {
                Assert::eq($current, $previous);
            }
        }
    }

    /**
     * @Given /^I change the (.*) to (.*)$/
     */
    public function iChangeThe($property, $value)
    {
        $this->reqBody[$property] = $value;
    }

    /**
     * @Given :property1 and :property2 are the same
     */
    public function propertiesAreTheSame($property1, $property2)
    {
        Assert::eq($this->userFromDb->$property1, $this->userFromDb->$property2);
    }
}
