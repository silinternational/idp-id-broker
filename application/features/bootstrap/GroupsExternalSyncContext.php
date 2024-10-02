<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use common\components\ExternalGroupsSync;
use common\models\EmailLog;
use common\models\User;
use Sil\PhpEnv\Env;
use Webmozart\Assert\Assert;

class GroupsExternalSyncContext extends GroupsExternalContext
{
    private string $errorsEmailRecipient = '';

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
        $this->syncErrors = ExternalGroupsSync::processUpdates(
            $appPrefix,
            $this->externalGroupsLists[$appPrefix] ?? [],
            $this->errorsEmailRecipient,
            'dummy-google-sheet-id'
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
        Assert::inArray(
            Env::get('MYSQL_DATABASE'),
            ['appfortests', 'test'],
            'This test should only be run against a test database (because it deletes users)'
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
     * @Then we should have sent exactly :expectedCount :appPrefix sync-error notification email
     */
    public function weShouldHaveSentExactlySyncErrorNotificationEmail($expectedCount, $appPrefix)
    {
        $fakeEmailer = $this->fakeEmailer;

        // The $appPrefix is needed by the FakeEmailer, to find the appropriate
        // emails (by being able to accurately generate the expected subject).
        $fakeEmailer->otherDataForEmails['appPrefix'] = $appPrefix;
        $syncErrorEmails = $fakeEmailer->getFakeEmailsOfTypeSentToUser(
            EmailLog::MESSAGE_TYPE_EXT_GROUP_SYNC_ERRORS,
            $this->errorsEmailRecipient
        );

        Assert::count($syncErrorEmails, $expectedCount, sprintf(
            'Expected %s sync-error emails (to %s), but found %s. Emails sent: %s',
            $expectedCount,
            $this->errorsEmailRecipient,
            count($syncErrorEmails),
            json_encode($fakeEmailer->getFakeEmailsSent(), JSON_PRETTY_PRINT)
        ));
    }

    /**
     * @Given we have provided an error-notifications email address
     */
    public function weHaveProvidedAnErrorNotificationsEmailAddress()
    {
        $this->errorsEmailRecipient = 'sync-errors@example.com';
    }
}
