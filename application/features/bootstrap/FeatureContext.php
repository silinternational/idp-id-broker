<?php

use Behat\Gherkin\Node\TableNode;
use common\helpers\MySqlDateTime;
use common\models\EmailLog;
use common\models\Invite;
use common\models\Method;
use common\models\Mfa;
use common\models\MfaBackupcode;
use common\models\MfaFailedAttempt;
use common\models\MfaWebauthn;
use common\models\Password;
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
    private $queryParams = [];

    /** @var Response */
    private $response;
    private $resBody = [];

    /** @var User */
    private $userFromDb;
    /** @var User */
    private $userFromDbBefore;

    /** @var Method */
    protected $methodFromDb;

    private $now;
    public const ACCEPTABLE_DELTA_IN_SECONDS = 1;

    protected $tempEmployeeId = null;

    protected $tempUid = null;

    public const CREATED = 'created';
    public const DELETED = 'deleted';
    public const RETRIEVED = 'retrieved';
    public const UPDATED = 'updated';

    /**
     * @Given I add a user with a(n) :property of :value
     */
    public function iAddAUserWithAnOf($property, $value)
    {
        $sampleUserData = [
            'employee_id' => '10000',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'display_name' => 'John Smith',
            'username' => 'john_smith',
            'email' => 'john_smith@example.org',
        ];
        $sampleUserData[$property] = $value;

        $this->tempEmployeeId = $sampleUserData['employee_id'];

        $dataForTableNode = [
            ['property', 'value'],
        ];
        foreach ($sampleUserData as $sampleProperty => $sampleValue) {
            $dataForTableNode[] = [$sampleProperty, $sampleValue];
        }
        $this->iProvideTheFollowingValidData(new TableNode($dataForTableNode));
        $this->iRequestTheResourceBe('/user', self::CREATED);
        $this->theResponseStatusCodeShouldBe(200);
    }

    /**
     * @Then I should receive :numRecords record(s)
     */
    public function iShouldReceiveRecords($numRecords)
    {
        $this->iShouldReceiveUsers($numRecords);
    }

    /**
     * @Then that record should have a data item with the following elements:
     */
    public function thatRecordShouldHaveADataItemWithTheFollowingElements(TableNode $table)
    {
        Assert::minCount($this->resBody, 1);
        $item = $this->resBody[0];
        Assert::minCount($item['data'], 1);
        $data = $item['data'][0];

        foreach ($table as $row) {
            $property = $row['property'];
            $expectedValue = $row['value'];

            if ($expectedValue == '*') {
                Assert::minLength($data[$property], 1);
            } else {
                Assert::eq($data[$property], $this->transformNULLs($expectedValue));
            }
        }
    }

    /**
     * @Given the requester is not authorized
     */
    public function theRequesterIsNotAuthorized()
    {
        unset($this->reqHeaders['Authorization']);
    }

    /**
     * @Given the user store is empty
     * @AfterSuite @database
     */
    public static function theUserStoreIsEmpty()
    {
        // To avoid calls to try to remove TOTP/WebAuthn entries from their
        // respective backend services, we are simply deleting all relevant
        // records here via deleteAll() to prevent their before/afterDelete()
        // functions from being called.
        MfaBackupcode::deleteAll();
        MfaFailedAttempt::deleteAll();
        MfaWebauthn::deleteAll();
        Mfa::deleteAll();
        Method::deleteAll();
        Invite::deleteAll();
        EmailLog::deleteAll();
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


    public function callU2fSimulator($resource, $action, User $user, string $externalId)
    {
        $webConfig = Yii::$app->components['webauthn'];

        $this->reqHeaders = array_merge($this->reqHeaders, [
            'x-mfa-RPID' => $webConfig['rpId'],
            'x-mfa-RPOrigin' => $webConfig['rpId'],
            'x-mfa-UserUUID' => $externalId,
            'Content-type' => 'application/json',
        ]);

        $client = $this->buildU2fClient();
        $this->response = $this->sendRequest($client, $action, $resource);

        $this->now = MySqlDateTime::now();
        $this->resBody = $this->extractBody($this->response);
    }

    private function buildU2fClient(): Client
    {
        $u2fSimAndPort = getenv('U2F_SIM_HOST_AND_PORT') ?: 'u2fsim:8080';
        return new Client([
            'base_uri' => $u2fSimAndPort,
            'http_errors' => false, // don't throw exceptions on 4xx/5xx so responses can be inspected.
            'headers' => $this->reqHeaders,
            'json' => $this->reqBody,
        ]);
    }

    private function sendRequest(Client $client, string $action, string $resource): ResponseInterface
    {
        switch ($action) {
            case self::CREATED:
                return $client->post($resource);
            case self::UPDATED:
                return $client->put($resource);
            case self::DELETED:
                return $client->delete($resource);
            case self::RETRIEVED:
                return $client->get($resource);
            case 'headed':
                return $client->head($resource);
            case 'patched':
                return $client->patch($resource);

            default: throw new InvalidArgumentException("$action is not a recognized HTTP verb.");
        }
    }

    /**
     * @When I send a :verb to :resource with a valid uid
     */
    public function iSendAToWithAValidUid($verb, $resource)
    {
        $client = $this->buildClient();

        $this->response = call_user_func(
            [$client, strtolower($verb)],
            str_replace('{uid}', $this->tempUid, $resource)
        );

        $this->now = MySqlDateTime::now();

        $this->resBody = $this->extractBody($this->response);
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
        Assert::eq(
            $this->response->getStatusCode(),
            $statusCode,
            sprintf(
                "Unexpected response. status=%d, body=%s",
                $this->response->getStatusCode(),
                var_export($this->resBody, true)
            )
        );
    }

    /**
     * @Then the response body should contain :containsText
     */
    public function theResponseBodyShouldContain($containsText)
    {
        Assert::contains(
            var_export($this->resBody, true),
            $containsText,
            sprintf(
                "Unexpected response body. Does not contain: %s, body=%s",
                $containsText,
                var_export($this->resBody, true)
            )
        );
    }

    /**
     * @Then the response body should not contain :notContainsText
     */
    public function theResponseBodyShouldNotContain($notContainsText)
    {
        Assert::notContains(
            var_export($this->resBody, true),
            $notContainsText,
            sprintf(
                "Unexpected response body. Should not contain: %s, body=%s",
                $notContainsText,
                var_export($this->resBody, true)
            )
        );
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
        $keys = Env::requireArray('API_ACCESS_KEYS');

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
     * @Given I provide the following (valid) data:
     */
    public function iProvideTheFollowingValidData(TableNode $data)
    {
        foreach ($data as $row) {
            $this->reqBody[$row['property']] = ($row['value'] === 'null' ? null : $row['value']);
        }
    }

    /**
     * @Then the following data is returned:
     */
    public function theFollowingDataIsReturned(TableNode $data)
    {
        foreach ($data as $row) {
            if (strpos($row['property'], '.') !== false) {
                $name = explode('.', $row['property'], 2);
                Assert::eq(
                    $this->resBody[$name[0]][$name[1]],
                    $row['value'],
                    sprintf(
                        '"%s" not equal to "%s", "%s" found',
                        $row['property'],
                        $row['value'],
                        $this->resBody[$name[0]][$name[1]]
                    )
                );
            } else {
                Assert::keyExists(
                    $this->resBody,
                    $row['property'],
                    'key ' . $row['property'] . ' not found in ' . var_export($this->resBody, true)
                );
                Assert::eq($this->resBody[$row['property']], $row['value']);
            }
        }
    }


    /**
     * @Given the following data is not returned:
     */
    public function theFollowingDataIsNotReturned(TableNode $data)
    {
        foreach ($data as $row) {
            Assert::keyNotExists($this->resBody, $row['property']);
        }
    }

    /**
     * @Then /^a record exists with (?:a|an) (.*) of "?([^"]*)"?$/
     */
    public function aRecordExistsForThisKey($lookupKey, $lookupValue)
    {
        $this->userFromDbBefore = $this->userFromDb;

        $this->userFromDb = User::findOne([$lookupKey => $lookupValue]);

        Assert::notNull($this->userFromDb, sprintf(
            'Failed to find a user with a %s of %s.',
            $lookupKey,
            var_export($lookupValue, true)
        ));
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
    protected function transformNULLs($value)
    {
        return ($value === "NULL" || $value === "null") ? null : $value;
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

        $storedNow = $this->userFromDb->$property ? strtotime($this->userFromDb->$property) : 0;

        Assert::range($storedNow, $minAcceptable, $maxAcceptable, "Stored time $storedNow is not within acceptable range of $minAcceptable to $maxAcceptable");
    }

    /**
     * @Then :property should not change
     *
     */
    public function shouldNotChange($property)
    {
        $valueBefore = $this->userFromDbBefore->$property;
        $valueNow = $this->userFromDb->$property;

        Assert::eq($valueBefore, $valueNow);
    }

    /**
     * @When I request :resource be created again
     */
    public function iRequestTheResourceBeCreatedAgain($resource)
    {
        $this->userFromDbBefore = $this->userFromDb;

        sleep(1); // so timestamps won't be the same

        $this->iRequestTheResourceBe($resource, self::CREATED);
    }

    /**
     * @Then the only property to change should be :property
     */
    public function theOnlyPropertyToChangeShouldBe($property)
    {
        foreach ($this->userFromDbBefore->attributes as $name => $value) {
            $previous = $this->userFromDbBefore->$name;
            $current = $this->userFromDb->$name;

            $name === $property ? Assert::notEq($current, $previous, "$property is equal. Previous: $previous, Current: $current")
                                : Assert::eq($current, $previous, "$property is not equal.  Previous: $previous, Current: $current");
        }
    }

    /**
     * @Given /^I change the (\w*) to (.*)$/
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

        $this->userFromDb->scenario = User::SCENARIO_UPDATE_USER;
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
     * @Given the user :username has no password in the database
     */
    public function theUserHasNoPasswordInTheDatabase($username)
    {
        $user = User::findByUsername($username);
        Assert::notNull($user);
        $user->current_password_id = null;
        Assert::true($user->save(false, ['current_password_id']));
        $user->refresh();
        Assert::null($user->current_password_id);
        Password::deleteAll(['user_id' => $user->id]);
    }

    /**
     * @Given the user :username does have a password in the database
     */
    public function theUserDoesHaveAPasswordInTheDatabase($username)
    {
        $user = User::findByUsername($username);
        Assert::notNull($user);
        Assert::notNull($user->currentPassword);
    }

    protected function createInviteCode($user, $code, $expired = false)
    {
        $inviteCode = new Invite();
        $inviteCode->uuid = $code;
        $inviteCode->user_id = $user->id;
        $inviteCode->expires_on = ($expired) ? '2018-01-01' : null;
        Assert::true(
            $inviteCode->save(),
            var_export($inviteCode->getErrors(), true)
        );
    }

    /**
     * @Given the user :username has an expired invite code :code
     */
    public function theUserHasAnExpiredInviteCode($username, $code)
    {
        $user = User::findByUsername($username);
        Assert::notNull($user);

        $this->createInviteCode($user, $code, true);
    }

    /**
     * @Given the user :username has a non-expired invite code :code
     */
    public function theUserHasANonExpiredInviteCode($username, $code)
    {
        $user = User::findByUsername($username);
        Assert::notNull($user);

        $this->createInviteCode($user, $code);
    }

    /**
     * @Given I do not provide an employee_id
     */
    public function iDoNotProvideAnEmployeeId()
    {
        unset($this->reqBody['employee_id']);
    }

    /**
     * @Given the response should contain a :key array with :num items
     */
    public function theResponseShouldContainAArrayWithItems($key, $num)
    {
        Assert::keyExists($this->resBody, $key);
        Assert::eq(count($this->resBody[$key]), $num);
    }

    /**
     * @param $property
     * @return mixed
     */
    public function getResponseProperty($property)
    {
        return $this->resBody[$property];
    }

    /**
     * @param $property
     * @return mixed
     */
    public function setRequestBody(string $key, $value)
    {
        $this->reqBody[$key] = $value;
    }

    /**
     * @param $property
     * @return mixed
     */
    public function cleanRequestBody()
    {
        $this->reqBody = [];
    }

    /**
     * @param $property
     * @return mixed
     */
    public function getResponseBody()
    {
        return $this->resBody;
    }

    /**
     * @Then /^a method record exists with (?:a|an) (.*) of "?([^"]*)"?$/
     */
    public function aMethodRecordExistsForThisKey($lookupKey, $lookupValue)
    {
        $this->methodFromDb = Method::findOne([$lookupKey => $lookupValue]);

        Assert::notNull($this->methodFromDb, sprintf(
            'Failed to find a method with a %s of %s.',
            $lookupKey,
            var_export($lookupValue, true)
        ));
    }

    /**
     * @Then a method record exists with a value of :address text to change signature
     */
    public function aMethodRecordExistsForEmployeeIdWithAValueOf($address)
    {
        $this->methodFromDb = Method::findOne(['value' => $address, 'user_id' => $this->userFromDb->id]);

        Assert::notNull($this->methodFromDb, sprintf(
            'Failed to find a method with a value of %s.',
            $address
        ));
    }


    /**
     * @Then the method record is marked as verified
     */
    public function theMethodRecordIsMarkedAsVerified()
    {
        Assert::eq(1, $this->methodFromDb->verified, 'method is not marked as verified');
    }

    /**
     * @Then a method record does not exist with a value of :address
     */
    public function aMethodRecordDoesNotExistForEmployeeIdWithAValueOf($address)
    {
        Assert::null(
            Method::findOne(['value' => $address, 'user_id' => $this->userFromDb->id]),
            'method should have been deleted but still exists'
        );
    }

    /**
     * @Then the profile review date should be past
     */
    public function theProfileReviewDateShouldBePast()
    {
        $this->userFromDb->refresh();
        Assert::true(MySqlDateTime::isBefore($this->userFromDb->review_profile_after, time()));
    }

    /**
     * @Given the user record for :username has expired
     */
    public function theUserRecordForHasExpired($username)
    {
        $user = User::findByUsername($username);
        $user->expires_on = '2000-01-01';
        $user->scenario = User::SCENARIO_UPDATE_USER;
        Assert::true($user->save());
    }

    /**
     * @Given there is a :username user in the database
     */
    public function thereIsAUserInTheDatabase($username)
    {
        $this->userFromDb = User::findOne(['username' => $username]);
    }

    /**
     * @Given that user has a :field in the :tense
     */
    public function thatUserHasAFieldInTheTense($field, $tense)
    {
        $relativeTimes = [
            'past' => '-1 day',
            'future' => '+1 day',
        ];

        $this->userFromDb->$field = MySqlDateTime::relative($relativeTimes[$tense]);
        $this->userFromDb->scenario = User::SCENARIO_UPDATE_USER;
        Assert::true($this->userFromDb->save());
    }

    /**
     * @Given I create the following users:
     */
    public function iCreateTheFollowingUsers(TableNode $data)
    {
        $dataArray = $data->getColumnsHash();
        foreach ($dataArray as $row) {
            $this->reqBody = $row;
            $this->iRequestTheResourceBe('/user', self::CREATED);
        }
    }
    /**
     * @Given I provide a(n) :field query property of :value
     */
    public function iProvideAFieldQueryPropertyOfValue($field, $value)
    {
        $this->queryParams[$field] = $value;
    }

    /**
     * @When I search by :field
     */
    public function iSearchByField($field)
    {
        $request = '/user?' . $field . '=' . $this->queryParams[$field];
        $this->iRequestTheResourceBe($request, self::RETRIEVED);
    }

    /**
     * @Then user :employeeId is returned
     */
    public function userIsReturned($employeeId)
    {
        $found = false;
        foreach ($this->resBody as $user) {
            if ($user['employee_id'] == $employeeId) {
                $found = true;
                break;
            }
        }
        Assert::true($found, 'user ' . $employeeId . ' was not returned');
    }

    /**
     * @Then no users are returned
     */
    public function noUsersAreReturned()
    {
        Assert::eq(count($this->resBody), 0, 'response is not empty');
    }

    /**
     * @Given the response should contain a :key array with only these elements:
     */
    public function theResponseShouldContainAMemberArrayWithOnlyTheseElements($key, TableNode $data)
    {
        $property = $this->resBody[$key];
        $n = 0;
        foreach ($data as $row) {
            $want = $row['element'];
            if ($want == '{idpName}') {
                $want = \Yii::$app->params['idpName'];
            }
            Assert::true(in_array($want, $property), '"' . $want . '" not in array: ' . json_encode($property));
            $n++;
        }

        Assert::eq(count($property), $n, "unexpected element(s) in property array: " .
            implode(", ", $property));
    }


    /**
     * @Then the uuid property should be a valid UUID
     */
    public function theUuidPropertyShouldBeAValidUuid()
    {
        Assert::regex(
            $this->resBody['uuid'],
            '/[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[1-4][0-9a-fA-F]{3}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}/'
        );
    }

    /**
     * @Given I wait until after the user :username expiration date
     */
    public function iWaitUntilAfterTheUserExpirationDate($username)
    {
        $this->userFromDb = User::findOne(['username' => $username]);
        $earlier = MySqlDateTime::relative("-1 year");
        $this->userFromDb->expires_on = MySqlDateTime::relative($earlier);
        $this->userFromDb->scenario = User::SCENARIO_UPDATE_USER;
        $this->userFromDb->save();
    }

    /**
     * @Given The user's current password should not be marked as pwned
     */
    public function theUserSCurrentPasswordShouldNotBeMarkedAsPwned()
    {
        $user = User::findOne(['username' => $this->reqBody['username']]);
        Assert::eq($user->currentPassword->hibp_is_pwned, "no");
    }

    /**
     * @Given The user's current password should be marked as pwned
     */
    public function theUserSCurrentPasswordShouldBeMarkedAsPwned()
    {
        $user = User::findOne(['username' => $this->reqBody['username']]);
        Assert::eq($user->currentPassword->hibp_is_pwned, "yes");
    }

    /**
     * @Given The user's password is not expired
     */
    public function theUserSPasswordIsNotExpired()
    {
        $user = User::findOne(['username' => $this->reqBody['username']]);
        Assert::greaterThanEq(strtotime($user->currentPassword->expires_on), time());
    }

    /**
     * @Given The user's password is expired
     */
    public function theUserSPasswordIsExpired()
    {
        $user = User::findOne(['username' => $this->reqBody['username']]);
        Assert::lessThanEq(strtotime($user->currentPassword->expires_on), time());
    }
}
