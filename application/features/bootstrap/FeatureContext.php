<?php

use Behat\Gherkin\Node\TableNode;
use common\helpers\MySqlDateTime;
use common\models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Sil\PhpEnv\Env;
use Sil\SilIdBroker\Behat\Context\YiiContext;
use Webmozart\Assert\Assert;
use yii\helpers\Json;

class FeatureContext extends YiiContext
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
    const ACCEPTABLE_DELTA_IN_SECONDS = 1;

    /**
     * @Given the requester is not authorized
     */
    public function theRequesterIsNotAuthorized()
    {
        unset($this->reqHeaders['Authorization']);
    }

    /**
     * @Given the user store is empty
     */
    public function theUserStoreIsEmpty()
    {
        User::deleteAll();
    }

    /**
     * @When /^I request "(.*)" be <?(created|updated|deleted|retrieved|headed|patched)>?$/
     */
    public function iRequestTheResourceBe($resource, $action)
    {
        $client = $this->buildClient();

        $this->response = $this->sendRequest($client, $action, $resource);

        $this->now = MySqlDateTime::now();

        $this->resBody = $this->extractBody($this->response);

    }

    private function buildClient(): Client
    {
        $hostname = Env::get('TEST_SERVER_HOSTNAME');

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
            case 'created'  : return $client->post  ($resource);
            case 'updated'  : return $client->put   ($resource);
            case 'deleted'  : return $client->delete($resource);
            case 'retrieved': return $client->get   ($resource);
            case 'headed'   : return $client->head  ($resource);
            case 'patched'  : return $client->patch ($resource);

            default: throw new InvalidArgumentException("$action is not a recognized HTTP verb.");
        }
    }

    private function extractBody(Response $response): array
    {
        $jsonBlob = $response->getBody()->getContents();

        return Json::decode($jsonBlob) ?? [];
    }

    /**
     * @Then the response status code should be :statusCode
     */
    public function theResponseStatusCodeShouldBe($statusCode)
    {
        Assert::eq($this->response->getStatusCode(), $statusCode);
    }

    /**
     * @Then /^the property (\w+) should contain "(.*)"$/
     */
    public function thePropertyShouldContain($property, $contents)
    {
        empty($contents) ? Assert::eq($this->resBody[$property], "")
                         : Assert::contains($this->resBody[$property], $contents);
    }

    /**
     * @Then the user store is still empty
     */
    public function thereAreStillNoUsers()
    {
        Assert::isEmpty(User::find()->all());
    }

    /**
     * @Given the requester is authorized
     */
    public function theRequesterIsAuthorized()
    {
        $keys = Env::getArray('API_ACCESS_KEYS');

        $this->reqHeaders['Authorization'] = 'Bearer ' . $keys[0];
    }

    /**
     * @Given /^then I remove the (.*)$/
     */
    public function thenIRemoveThe($property)
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
     * @Given /^a record does not exist with (?:a|an) (.*) of "?([^"]*)"?$/
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
     * @Then /^a record exists with (?:a|an) (.*) of "?([^"]*)"?$/
     */
    public function aRecordExistsForThisKey($lookupKey, $lookupValue)
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
     *
     * The time between the request being made and that data being stored might have
     * some latency so "now" may not be the same in every circumstance, therefore a
     * range of acceptable time will be used to determine accuracy.
     */
    public function shouldBeStoredAsNowUTC($property)
    {
        $expectedNow = strtotime($this->now);

        $minAcceptable = $expectedNow - self::ACCEPTABLE_DELTA_IN_SECONDS;
        $maxAcceptable = $expectedNow + self::ACCEPTABLE_DELTA_IN_SECONDS;

        $storedNow = strtotime($this->userFromDb->$property);

        Assert::range($storedNow, $minAcceptable, $maxAcceptable);
    }

    /**
     * @When I request :resource be created again
     */
    public function iRequestTheResourceBeCreatedAgain($resource)
    {
        $this->userFromDbBefore = $this->userFromDb;

        sleep(1); // so timestamps won't be the same

        $this->iRequestTheResourceBe($resource, 'created');
    }

    /**
     * @Then the only property to change should be :property
     */
    public function theOnlyPropertyToChangeShouldBe($property)
    {
        foreach ($this->userFromDbBefore->attributes as $name => $value) {
            $previous = $this->userFromDbBefore->$name;
            $current = $this->userFromDb->$name;

            $name === $property ? Assert::notEq($current, $previous)
                                : Assert::eq($current, $previous);
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

    /**
     * @Given the user has a password of :password
     */
    public function theUserHasAPasswordOf($password)
    {
        $this->userFromDb->scenario = User::SCENARIO_UPDATE_PASSWORD;

        $this->userFromDb->password = $password;

        Assert::true($this->userFromDb->save());
    }

    /**
     * @Then /^a record still exists with (?:a|an) (.*) of "?([^"]*)"?$/
     */
    public function aRecordStillExistsForThisKey($lookupKey, $lookupValue)
    {
        $this->userFromDbBefore = $this->userFromDb;

        $this->aRecordExistsForThisKey($lookupKey, $lookupValue);
    }

    /**
     * @Then none of the data has changed
     */
    public function noneOfTheDataHasChanged()
    {
        foreach ($this->userFromDbBefore->attributes as $name => $value) {
            $previous = $this->userFromDbBefore->$name;
            $current = $this->userFromDb->$name;

            Assert::eq($previous, $current);
        }
    }

    /**
     * @Then the authentication is not successful
     */
    public function theAuthenticationIsNotSuccessful()
    {
        $this->theResponseStatusCodeShouldBe(400);
        $this->thePropertyShouldContain("message", "");
    }

    /**
     * @Given /^the (.*) is stored as (.*)$/
     */
    public function thePropertyIsStoredAs($property, $value)
    {
        $this->userFromDb->$property = $value;

        Assert::true($this->userFromDb->save());
    }

    /**
     * @Given /^I should receive (\d+) users$/
     */
    public function iShouldReceiveUsers($numOfUsers)
    {
        Assert::count($this->resBody, $numOfUsers);
    }

    /**
     * @Given there is a(n) :username user in the ldap with a password of :password
     */
    public function thereIsAnUserInTheLdapWithAPasswordOf($username, $password)
    {
        $isCorrect = Yii::$app->ldap->isPasswordCorrectForUser(
            $username,
            $password
        );
        Assert::true($isCorrect);
    }

    /**
     * @Given the user :username has no password in the database
     */
    public function theUserHasNoPasswordInTheDatabase($username)
    {
        $user = User::findByUsername($username);
        Assert::notNull($user);
        $user->password_hash = null;
        Assert::true($user->save(false, ['password_hash']));
        $user->refresh();
        Assert::null($user->password_hash);
    }

    /**
     * @Given the user :username does have a password in the database
     */
    public function theUserDoesHaveAPasswordInTheDatabase($username)
    {
        $user = User::findByUsername($username);
        Assert::notNull($user);
        Assert::notNull($user->password_hash);
    }
}
