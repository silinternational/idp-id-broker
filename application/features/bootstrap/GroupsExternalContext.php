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
        $this->deleteThatTestUser();
        $this->createTestUser();
        $this->setThatUsersPassword($this->userPassword);
    }

    private function deleteThatTestUser()
    {
        $user = User::findByEmail($this->userEmailAddress);
        if ($user !== null) {
            $didDeleteUser = $user->delete();
            Assert::notFalse($didDeleteUser, sprintf(
                'Failed to delete existing test user: %s',
                join("\n", $user->getFirstErrors())
            ));
        }
    }

    private function createTestUser()
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
        $user->refresh();

        $this->user = $user;
    }

    private function setThatUsersPassword(string $password)
    {
        $this->user->scenario = User::SCENARIO_UPDATE_PASSWORD;
        $this->user->password = $password;

        Assert::true($this->user->save(), sprintf(
            "Failed to set the test user's password: %s",
            join("\n", $this->user->getFirstErrors())
        ));
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
        $this->iRequestTheResourceBe('/authentication', 'created');
        $this->theResponseStatusCodeShouldBe(200);
    }

    /**
     * @Then the member list will include the following groups:
     */
    public function theMemberListWillIncludeTheFollowingGroups(TableNode $table)
    {
        $memberList = $this->getResponseProperty('member');
        Assert::notEmpty($memberList);

        foreach ($table as $row) {
            $group = $row['group'];
            Assert::inArray($group, $memberList, sprintf(
                'Expected to find group %s, but only found %s. User: %s',
                $group,
                join(', ', $memberList),
                json_encode($this->user->attributes, JSON_PRETTY_PRINT)
            ));
        }
    }
}
