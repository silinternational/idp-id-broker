<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use common\models\User;
use Webmozart\Assert\Assert;

class GroupsExternalSyncContext extends GroupsExternalContext
{
    /**
     * The lists of external groups for use by these tests. Example:
     * ```
     * [
     *     'wiki' => [
     *         'john_smith@example.org' => 'wiki-one,wiki-two',
     *     ],
     * ]
     * ```
     * @var array<string,string[]>
     */
    private array $externalGroupsLists = [];

    /**
     * @Given the following users exist, with these external groups:
     */
    public function theFollowingUsersExistWithTheseExternalGroups(TableNode $table)
    {
        $dummyEmployeeId = 11110;
        foreach ($table as $row) {
            $emailAddress = $row['email'];
            $employeeId = ++$dummyEmployeeId;
            $groups = $row['groups'];

            $this->deleteTestUser($emailAddress);
            $this->createTestUser(
                $emailAddress,
                (string)$employeeId,
                $groups
            );
        }
    }

    /**
     * @Given the :appPrefix external groups list is the following:
     */
    public function theExternalGroupsListIsTheFollowing(string $appPrefix, TableNode $table)
    {
        $userGroupsMap = [];
        foreach ($table as $row) {
            $emailAddress = $row['email'];
            $externalGroupsCsv = $row['groups'];
            $userGroupsMap[$emailAddress] = $externalGroupsCsv;
        }
        $this->externalGroupsLists[$appPrefix] = $userGroupsMap;

        //// TEMP
        //echo json_encode($this->externalGroupsLists, JSON_PRETTY_PRINT) . PHP_EOL;
    }

    /**
     * @When I sync the list of :appPrefix external groups
     */
    public function iSyncTheListOfExternalGroups($appPrefix)
    {
        User::syncExternalGroups(
            $appPrefix,
            $this->externalGroupsLists[$appPrefix]
        );
    }

    /**
     * @Then the following users should have the following external groups:
     */
    public function theFollowingUsersShouldHaveTheFollowingExternalGroups(TableNode $table)
    {
        foreach ($table as $row) {
            $emailAddress = $row['email'];
            $expectedExternalGroups = $row['groups'];

            $user = User::findByEmail($emailAddress);
            Assert::notNull($emailAddress, 'User not found: ' . $emailAddress);
            Assert::same($user->groups_external, $expectedExternalGroups);
        }
    }
}
