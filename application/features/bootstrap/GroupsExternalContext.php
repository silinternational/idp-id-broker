<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use common\models\User;
use FeatureContext;
use Webmozart\Assert\Assert;

class GroupsExternalContext extends FeatureContext
{
    private User $user;
    private string $userEmailAddress = 'john_smith@example.org';
    private string $userPassword = 'dummy-password-#1';

    /**
     * @Given a user exists
     */
    public function aUserExists()
    {
        $this->deleteTestUser($this->userEmailAddress);

        $this->user = $this->createTestUser(
            $this->userEmailAddress,
            '11111'
        );

        $this->setTestUsersPassword(
            $this->user,
            $this->userPassword
        );
    }

    protected function deleteTestUser(string $emailAddress)
    {
        $user = User::findByEmail($emailAddress);
        if ($user !== null) {
            $didDeleteUser = $user->delete();
            Assert::notFalse($didDeleteUser, sprintf(
                'Failed to delete existing test user (%s): %s',
                $emailAddress,
                join("\n", $user->getFirstErrors())
            ));
        }
    }

    protected function createTestUser(
        string $emailAddress,
        string $employeeId,
        string $externalGroups = ''
    ): User {
        list($username, ) = explode('@', $emailAddress);
        list($lcFirstName, $lcLastName) = explode('_', $username);
        $user = new User([
            'email' => $emailAddress,
            'employee_id' => $employeeId,
            'first_name' => ucfirst($lcFirstName),
            'last_name' => ucfirst($lcLastName),
            'username' => $username,
            'groups_external' => $externalGroups,
        ]);
        $user->scenario = User::SCENARIO_NEW_USER;

        $createdNewUser = $user->save();
        Assert::true($createdNewUser, sprintf(
            'Failed to create test user %s: %s',
            json_encode($emailAddress),
            join("\n", $user->getFirstErrors())
        ));
        $user->refresh();
        return $user;
    }

    private function setTestUsersPassword(User $user, string $password)
    {
        $user->scenario = User::SCENARIO_UPDATE_PASSWORD;
        $user->password = $password;

        Assert::true($user->save(), sprintf(
            "Failed to set the %s test user's password: %s",
            json_encode($user->email),
            join("\n", $user->getFirstErrors())
        ));
    }

    protected function getUserEmailAddress(): string
    {
        return $this->userEmailAddress;
    }

    /**
     * @Given that user's list of groups is :commaSeparatedGroups
     */
    public function thatUsersListOfGroupsIs($commaSeparatedGroups)
    {
        $this->user->groups = $commaSeparatedGroups;
        $this->user->scenario = User::SCENARIO_UPDATE_USER;

        $savedChanges = $this->user->save();
        Assert::true($savedChanges, sprintf(
            'Failed to set list of `groups` on test user: %s',
            join("\n", $this->user->getFirstErrors())
        ));
    }

    /**
     * @Given that user's list of external groups is :commaSeparatedExternalGroups
     */
    public function thatUsersListOfExternalGroupsIs($commaSeparatedExternalGroups)
    {
        $this->user->groups_external = $commaSeparatedExternalGroups;
        $this->user->scenario = User::SCENARIO_UPDATE_USER;

        $savedChanges = $this->user->save();
        Assert::true($savedChanges, sprintf(
            'Failed to set list of `groups_external` on test user: %s',
            join("\n", $this->user->getFirstErrors())
        ));
    }

    /**
     * @When I sign in as that user
     */
    public function iSignInAsThatUser()
    {
        $dataForTableNode = [
            ['property', 'value'],
            ['username', $this->user->username],
            ['password', $this->userPassword],
        ];
        $this->iProvideTheFollowingValidData(new TableNode($dataForTableNode));
        $this->iRequestTheResourceBe('/authentication', self::CREATED);
        $this->theResponseStatusCodeShouldBe(200);
    }
}
