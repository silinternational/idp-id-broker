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
     * @Then the response should only include the following users and groups:
     */
    public function theResponseShouldOnlyIncludeTheFollowingUsersAndGroups(TableNode $table)
    {
        $expected = [];
        foreach ($table as $row) {
            $expected[] = $row;
        }
        Assert::eq(
            json_encode($this->getResponseBody(), JSON_PRETTY_PRINT),
            json_encode($expected, JSON_PRETTY_PRINT)
        );
    }
}
