<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use common\models\User;
use Webmozart\Assert\Assert;

class GroupsExternalContext extends YiiContext
{
    private string $userEmailAddress = 'john_smith@example.org';
    private User $user;

    /**
     * @Given a user exists
     */
    public function aUserExists()
    {
        $user = User::findByEmail($this->userEmailAddress);
        if ($user === null) {
            $user = $this->createTestUser();
        }
        $this->user = $user;
    }

    private function createTestUser(): User
    {
        $user = new User([
            'email' => $this->userEmailAddress,
            'employee_id' => '11111',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'username' => 'john_smith',
        ]);
        $user->scenario = User::SCENARIO_NEW_USER;

        $createdNewUser = $user->save();
        Assert::true($createdNewUser, sprintf(
            'Failed to create test user: %s',
            join("\n", $user->getFirstErrors())
        ));
        return $user;
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
        throw new PendingException();
    }

    /**
     * @Then the members list will be :arg1
     */
    public function theMembersListWillBe($arg1)
    {
        throw new PendingException();
    }
}
