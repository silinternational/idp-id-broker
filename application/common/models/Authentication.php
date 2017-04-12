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
     * @param Ldap $ldap The LDAP to use for lazy-loading passwords not yet
     *     stored in our local database.
     */
    public function __construct(
        string $username,
        string $password,
        Ldap $ldap
    ) {
        /* @var $user User */
        $user = User::findByUsername($username) ?? new User();

        $user->scenario = User::SCENARIO_AUTHENTICATE;

        $user->attributes = [
            'username' => $username,
            'password' => $password,
        ];
        $user->setLdap($ldap);

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
