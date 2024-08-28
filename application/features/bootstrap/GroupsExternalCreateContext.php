<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Gherkin\Node\TableNode;

class GroupsExternalCreateContext extends GroupsExternalUpdatesContext
{
    /**
     * @When I set that user's list of :appPrefix external groups to the following:
     */
    public function iSetThatUsersListOfExternalGroupsToTheFollowing($appPrefix, TableNode $table)
    {
        $externalGroups = [];
        foreach ($table as $row) {
            $externalGroups[] = $row['externalGroup'];
        }

        $this->cleanRequestBody();
        $this->setRequestBody('email', $this->getUserEmailAddress());
        $this->setRequestBody(
            'groups',
            join(',', $externalGroups)
        );

        $urlPath = sprintf(
            '/user/external-groups?app_prefix=%s',
            urlencode($appPrefix),
        );

        $this->iRequestTheResourceBe($urlPath, 'created');
    }
}
