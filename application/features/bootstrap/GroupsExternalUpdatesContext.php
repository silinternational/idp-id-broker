<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use common\models\User;
use Webmozart\Assert\Assert;

class GroupsExternalUpdatesContext extends GroupsExternalContext
{
    /**
     * @When I update that user's list of :appPrefix external groups to the following:
     */
    public function iUpdateThatUsersListOfExternalGroupsToTheFollowing($appPrefix, TableNode $table)
    {
        $externalGroups = [];
        foreach ($table as $row) {
            $externalGroups[] = $row['externalGroup'];
        }

        $this->cleanRequestBody();
        $this->setRequestBody('email_address', $this->getUserEmailAddress());
        $this->setRequestBody('app_prefix', $appPrefix);
        $this->setRequestBody('groups', $externalGroups);

        $this->iRequestTheResourceBe('/user/external-groups', 'updated');
    }

    /**
     * @Then that user's list of external groups should be :commaSeparatedExternalGroups
     */
    public function thatUsersListOfExternalGroupsShouldBe($commaSeparatedExternalGroups)
    {
        $user = User::findByEmail($this->getUserEmailAddress());
        Assert::same($user->groups_external, $commaSeparatedExternalGroups);
    }
}
