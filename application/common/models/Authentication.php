<?php

namespace common\models;

use yii\web\HttpException;

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
     */
    public function __construct(
        string $username,
        string $password,
        string $invite = ''
    ) {
        if (empty($invite)) {
            $this->authenticateByPassword($username, $password);
        } else {
            $this->authenticateByInvite($invite);
        }
    }

    /**
     * Attempt an authentication by password.
     *
     * @param string $username The username to try.
     * @param string $password The password to try.
     */
    protected function authenticateByPassword(string $username, string $password)
    {
        /* @var $user User */
        $user = User::findByUsername($username) ??
                User::findByEmail($username)    ?? // maybe we got an email
                new User();

        $user->scenario = User::SCENARIO_AUTHENTICATE;
        $user->password = $password;

        $this->validateUser($user);
    }

    /**
     * Attempt an authentication by new user invite.
     *
     * @param string $invite New user invite code. If not blank, username and password are ignored.
     * @throws HttpException
     */
    protected function authenticateByInvite($invite)
    {
        /* @var $invite Invite */
        $invite = Invite::findOne(['uuid' => $invite]);
        if ($invite === null) {
            $this->errors['invite'] = ['Invalid code.'];
            return;
        }

        /* @var $user User */
        $user = $invite->user;

        if ($user->current_password_id !== null) {
            $this->errors['invite'] = ['Invitation invalid. User has a password.'];
            return;
        }

        if ($invite->isExpired()) {
            $emailData = ['inviteCode' => $invite->renew()];

            /* @var $emailer Emailer */
            $emailer = \Yii::$app->emailer;
            $emailer->sendMessageTo(EmailLog::MESSAGE_TYPE_INVITE, $invite->user, $emailData);

            throw new HttpException(410);
        }

        $user->scenario = User::SCENARIO_INVITE;
        $this->validateUser($user);
    }
    /**
     * Run User validation rules. If all rules pass, $this->authenticatedUser will be a
     * clone of the User, and the User record in the database will be updated with new
     * reminder dates.
     *
     * @param User $user
     */
    protected function validateUser(User $user)
    {
        if ($user->validate()) {
            $this->authenticatedUser = clone $user;

            $user->updateProfileReviewDates();

            $user->checkAndProcessHIBP();

            if (!$user->save()) {
                \Yii::error([
                    'action' => 'save nag dates for user after authentication',
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
