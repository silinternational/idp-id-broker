<?php
namespace common\models;

use common\helpers\MySqlDateTime;
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
        $user = User::findByUsername($username) ??
                User::findByEmail($username)    ?? // maybe we got an email
                new User();

        $user->scenario = User::SCENARIO_AUTHENTICATE;

        if ($ldap instanceof Ldap) {
            $user->setLdap($ldap);
        }

        $user->password = $password;

        if ($user->validate()) {
            /*
             * Update last_login_utc and nag_for_mfa_after if unable to save log
             * error and proceed without stopping user
             */
            $user->last_login_utc = MySqlDateTime::now();
            $user->nag_for_mfa_after = MySqlDateTime::relative(\Yii::$app->params['mfaNagInterval']);
            if ( ! $user->save() ){
                \Yii::error([
                    'action' => 'save last_login_utc and nag_for_mfa_after for user after authentication',
                    'status' => 'error',
                    'message' => $user->getFirstErrors(),
                ]);
            }

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
