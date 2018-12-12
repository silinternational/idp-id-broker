<?php
namespace Sil\SilIdBroker\Behat\Context;

use common\ldap\Ldap;
use common\models\Authentication;
use common\models\User;
use Sil\Psr3Adapters\Psr3ConsoleLogger;
use Sil\SilIdBroker\Behat\Context\fakes\FakeOfflineLdap;
use Webmozart\Assert\Assert;
use Yii;

class LdapContext extends YiiContext
{
    /**
     *
     * @var User|null
     */
    private $authenticatedUser = null;

    private $authenticationErrors = null;

    /**
     *
     * @var Ldap
     */
    private $ldap;

    public function __construct()
    {
        $this->ldap = Yii::$app->ldap;
        $this->ldap->logger = new Psr3ConsoleLogger();
    }

    /**
     * Create a new user in the database with the given username (and other
     * details based off that username). If a user already exists with that
     * username, they will be deleted.
     *
     * @param string $username
     * @return User
     */
    protected function createNewUserInDatabase($username)
    {
        $existingUser = User::findByUsername($username);
        if ($existingUser !== null) {
            Assert::notSame($existingUser->delete(), false);
        }

        $user = new User([
            'email' => $username . '@example.com',
            'employee_id' => (string)time(),
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => $username,
        ]);
        $user->scenario = User::SCENARIO_NEW_USER;
        Assert::true(
            $user->save(),
            var_export($user->getErrors(), true)
        );
        Assert::notNull($user);
        return $user;
    }

    /**
     * @Given there is a :username user in the database with no password
     */
    public function thereIsAUserInTheDatabaseWithNoPassword($username)
    {
        $user = $this->createNewUserInDatabase($username);
        $user->current_password_id = null;
        Assert::true(
            $user->save(false, ['current_password_id']),
            var_export($user->getErrors(), true)
        );
        Assert::true($user->refresh());
        Assert::null($user->currentPassword);
    }

    /**
     * @Given there is a :username user in the ldap with a password of :password
     */
    public function thereIsAUserInTheLdapWithAPasswordOf($username, $password)
    {
        Assert::true($this->ldap->userExists($username));
        Assert::true($this->ldap->isPasswordCorrectForUser($username, $password));
    }

    /**
     * @When I try to authenticate as :username using :password
     */
    public function iTryToAuthenticateAsUsing($username, $password)
    {
        $authentication = new Authentication(
            $username,
            $password,
            $this->ldap
        );
        $this->authenticatedUser = $authentication->getAuthenticatedUser();
        $this->authenticationErrors = $authentication->getErrors();
    }

    /**
     * @Then the authentication should NOT be successful
     */
    public function theAuthenticationShouldNotBeSuccessful()
    {
        Assert::notInstanceOf($this->authenticatedUser, User::class);
        Assert::notEmpty($this->authenticationErrors);
    }

    /**
     * @Then the authentication SHOULD be successful
     */
    public function theAuthenticationShouldBeSuccessful()
    {
        Assert::isInstanceOf($this->authenticatedUser, User::class, sprintf(
            "Error(s): \n%s",
            var_export($this->authenticationErrors, true)
        ));
        Assert::isEmpty($this->authenticationErrors);
    }

    /**
     * @Given there is a :username user in the database with a password of :password
     */
    public function thereIsAUserInTheDatabaseWithAPasswordOf($username, $password)
    {
        $user = $this->createNewUserInDatabase($username);
        $user->scenario = User::SCENARIO_UPDATE_PASSWORD;
        $user->password = $password;
        Assert::true(
            $user->save(),
            var_export($user->getErrors(), true)
        );
    }

    /**
     * @Given the LDAP is offline
     */
    public function theLdapIsOffline()
    {
        $this->ldap = new FakeOfflineLdap([
            'domain_controllers' => $this->ldap->domain_controllers,
        ]);
    }

    /**
     * @Given LDAP password migration is disabled
     */
    public function ldapPasswordMigrationIsDisabled()
    {
        $this->ldap = null;
    }
}
