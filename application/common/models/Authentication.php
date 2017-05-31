<?php
namespace common\models;

use common\ldap\Ldap;

/**
 * An immutable class for checking authentication credentials.
 */
class Authentication
{
    private $authenticatedUser = null;
    private $errors = [];

    /**
     * Attempt an authentication.
     *
     * @param string $username The username to try.
     * @param string $password The password to try.
     * @param Ldap|null $ldap (Optional:) The LDAP to use for lazy-loading
     *     passwords not yet stored in our local database. Defaults to null,
     *     meaning passwords will not be migrated.
     */
    public function __construct(
        string $username,
        string $password,
        $ldap = null
    ) {
        /* @var $user User */
        $user = User::findByUsername($username) ?? new User();

        $user->scenario = User::SCENARIO_AUTHENTICATE;

        $user->attributes = [
            'username' => $username,
            'password' => $password,
        ];
        if ($ldap instanceof Ldap) {
            $user->setLdap($ldap);
        }

        if ($user->validate()) {
            $this->authenticatedUser = $user;
        } else {
            $this->errors = $user->getErrors();
        }
    }

    /**
     * Get the authenticated User (if authentication was successful) or null.
     *
     * @return User|null
     */
    public function getAuthenticatedUser()
    {
        return $this->authenticatedUser;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
