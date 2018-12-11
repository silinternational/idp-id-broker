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
     * @param string $code New user invite code. If not blank, username and password are ignored.
     * @param Ldap|null $ldap (Optional:) The LDAP to use for lazy-loading
     *     passwords not yet stored in our local database. Defaults to null,
     *     meaning passwords will not be migrated.
     */
    public function __construct(
        string $username,
        string $password,
        string $code = null,
        $ldap = null
    ) {
        if ($code == null || $code == '') {
            /* @var $user User */
            $user = User::findByUsername($username) ??
                    User::findByEmail($username)    ?? // maybe we got an email
                    new User();

            $user->scenario = User::SCENARIO_AUTHENTICATE;
            $user->password = $password;

            if ($ldap instanceof Ldap) {
                $user->setLdap($ldap);
            }
        } else {
            /* @var $newUserCode NewUserCode */
            $newUserCode = NewUserCode::findOne(['uuid' => $code]);
            if ($newUserCode === null) {
                $this->errors = ['Invalid code.'];
                return;
            }
            if ( ! $newUserCode->isValidCode()) {
                $this->errors = ['Expired code.'];
                return;
            }

            /* @var $user User */
            $user = $newUserCode->user;
            $user->scenario = User::SCENARIO_NEW_USER_CODE;

            if($user->current_password_id !== null) {
                $this->errors = ['Code invalid. Password has been set.'];
                return;
            }
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
