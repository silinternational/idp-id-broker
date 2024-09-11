<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use common\components\Emailer;
use common\models\User;
use Sil\PhpEnv\Env;
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

    /** @var string[] */
    private array $syncErrors;

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
    }

    /**
     * @When I sync the list of :appPrefix external groups
     */
    public function iSyncTheListOfExternalGroups($appPrefix)
    {
        $this->syncErrors = User::updateUsersExternalGroups(
            $appPrefix,
            $this->externalGroupsLists[$appPrefix] ?? []
        );
    }

    /**
     * @Then there should not have been any sync errors
     */
    public function thereShouldNotHaveBeenAnySyncErrors()
    {
        Assert::isEmpty($this->syncErrors, sprintf(
            "Unexpected sync error(s): \n%s",
            implode("\n", $this->syncErrors)
        ));
    }

    /**
     * @Then the following users should have the following external groups:
     */
    public function theFollowingUsersShouldHaveTheFollowingExternalGroups(TableNode $table)
    {
        foreach ($table as $row) {
            $emailAddress = $row['email'];
            $expectedExternalGroups = explode(',', $row['groups']);

            $user = User::findByEmail($emailAddress);
            Assert::notNull($emailAddress, 'User not found: ' . $emailAddress);
            $actualExternalGroups = explode(',', $user->groups_external);

            sort($actualExternalGroups);
            sort($expectedExternalGroups);

            Assert::same(
                json_encode($actualExternalGroups, JSON_PRETTY_PRINT),
                json_encode($expectedExternalGroups, JSON_PRETTY_PRINT)
            );
        }
    }

    /**
     * @Then there should have been a sync error
     */
    public function thereShouldHaveBeenASyncError()
    {
        Assert::notEmpty(
            $this->syncErrors,
            'Expected sync errors, but found none.'
        );
        foreach ($this->syncErrors as $syncError) {
            echo $syncError . PHP_EOL;
        }
    }

    /**
     * @Then there should have been a sync error that mentions :text
     */
    public function thereShouldHaveBeenASyncErrorThatMentions($text)
    {
        $foundMatch = false;
        foreach ($this->syncErrors as $syncError) {
            echo $syncError . PHP_EOL;
            if (str_contains($syncError, $text)) {
                $foundMatch = true;
            }
        }
        Assert::true($foundMatch, sprintf(
            "Did not find a sync error that mentions '%s'",
            $text
        ));
    }

    /**
     * @Given only the following users exist, with these external groups:
     */
    public function onlyTheFollowingUsersExistWithTheseExternalGroups(TableNode $table)
    {
        Assert::eq(
            Env::get('MYSQL_DATABASE'),
            'appfortests',
            'This test should only be run against the test database (it deletes users)'
        );

        $usersThatShouldExist = [];
        foreach ($table as $row) {
            $usersThatShouldExist[] = $row['email'];
        }

        $allUsers = User::find()->all();
        foreach ($allUsers as $user) {
            if (!in_array($user->email, $usersThatShouldExist, true)) {
                Assert::notFalse($user->delete(), 'Failed to delete user for test');
            }
        }
    }

    /**
     * @Then we should have sent exactly :expectedCount sync-error notification email
     */
    public function weShouldHaveSentExactlySyncErrorNotificationEmail($expectedCount)
    {
        $emails = $this->fakeEmailer->getFakeEmailsSent();
        $syncErrorEmails = [];

        foreach ($emails as $email) {
            if ($email[Emailer::PROP_SUBJECT] === $this->fakeEmailer->subjectForSyncErrors) {
                $syncErrorEmails[] = $email;
            }
        }

        Assert::count($syncErrorEmails, $expectedCount);
    }
}
