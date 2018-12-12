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
     * @param string $invite New user invite code. If not blank, username and password are ignored.
     * @param Ldap|null $ldap (Optional:) The LDAP to use for lazy-loading
     *     passwords not yet stored in our local database. Defaults to null,
     *     meaning passwords will not be migrated.
     */
    public function __construct(
        string $username,
        string $password,
        string $invite = '',
        $ldap = null
    ) {
        if ($invite == '') {
            $this->authenticateByPassword($username, $password, $ldap);
        } else {
            $this->authenticateByInvite($invite);
        }
    }

    /**
     * Attempt an authentication by password.
     *
     * @param string $username The username to try.
     * @param string $password The password to try.
     * @param Ldap|null $ldap (Optional:) The LDAP to use for lazy-loading
     *     passwords not yet stored in our local database. Defaults to null,
     *     meaning passwords will not be migrated.
     */
    protected function authenticateByPassword(string $username, string $password, $ldap)
    {
        /* @var $user User */
        $user = User::findByUsername($username) ??
                User::findByEmail($username)    ?? // maybe we got an email
                new User();

        $user->scenario = User::SCENARIO_AUTHENTICATE;
        $user->password = $password;

        if ($ldap instanceof Ldap) {
            $user->setLdap($ldap);
        }

        $this->validateUser($user);
    }

    /**
     * Attempt an authentication by new user invite.
     *
     * @param string $invite New user invite code. If not blank, username and password are ignored.
     */
    protected function authenticateByInvite($invite)
    {
        /* @var $invite Invite */
        $invite = Invite::findOne(['uuid' => $invite]);
        if ($invite === null) {
            $this->errors['invite'] = ['Invalid code.'];
            return;
        }
        if ( ! $invite->isValidCode()) {
            $this->errors = $invite->getErrors();
            return;
        }

        /* @var $user User */
        $user = $invite->user;
        $user->scenario = User::SCENARIO_INVITE;

        if($user->current_password_id !== null) {
            $this->errors['invite'] = ['Invitation invalid. User has a password.'];
            return;
        }

        $this->validateUser($user);
    }
    /**
     * Run User validation rules. If all rules pass, $this->authenticatedUser will be a
     * clone of the User, and the User record in the database will be updated with new
     * login and reminder dates.
     *
     * @param User $user
     */
    protected function validateUser(User $user)
    {
        if ($user->validate()) {

            $this->authenticatedUser = clone $user;

            $user->last_login_utc = MySqlDateTime::now();

            $user->updateNagDates();

            if ( ! $user->save() ){
                \Yii::error([
                    'action' => 'save last_login_utc and nag dates for user after authentication',
                    'status' => 'error',
                    'message' => $user->getFirstErrors(),
                ]);
            }
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
