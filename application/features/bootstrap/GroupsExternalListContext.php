<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Webmozart\Assert\Assert;

class GroupsExternalListContext extends GroupsExternalContext
{
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
     * @When I get the list of users with :appPrefix external groups
     */
    public function iGetTheListOfUsersWithExternalGroups($appPrefix)
    {
        $this->cleanRequestBody();

        $urlPath = sprintf(
            '/user/external-groups?app_prefix=%s',
            urlencode($appPrefix),
        );

        $this->iRequestTheResourceBe($urlPath, 'retrieved');
    }

    /**
     * @Then the response should only include the following groups for the following users:
     */
    public function theResponseShouldOnlyIncludeTheFollowingGroupsForTheFollowingUsers(TableNode $table)
    {
        $expectedGroupsByUser = [];
        foreach ($table as $row) {
            $expectedGroupsByUser[$row['email']] = $row['groups'];
        }

        $actualGroupsByUser = [];
        $responseBody = $this->getResponseBody();
        foreach ($responseBody as $responseEntry) {
            $actualGroupsByUser[$responseEntry['email']] = $responseEntry['groups'];
        }

        Assert::eq(
            json_encode($actualGroupsByUser, JSON_PRETTY_PRINT),
            json_encode($expectedGroupsByUser, JSON_PRETTY_PRINT)
        );
    }
}
