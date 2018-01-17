<?php
namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use common\models\Mfa;
use common\models\MfaBackupcode;
use common\models\User;
use Webmozart\Assert\Assert;

class MfaContext extends \FeatureContext
{
    protected $tempEmployeeId = null;
    
    /**
     * @Given I add a user with a(n) :property of :value
     */
    public function iAddAUserWithAnOf($property, $value)
    {
        $sampleUserData = [
            'employee_id' => '10000',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'display_name' => 'John Smith',
            'username' => 'john_smith',
            'email' => 'john_smith@example.org',
        ];
        $sampleUserData[$property] = $value;
        
        $this->tempEmployeeId = $sampleUserData['employee_id'];
        
        $dataForTableNode = [
            ['property', 'value'],
        ];
        foreach ($sampleUserData as $sampleProperty => $sampleValue) {
            $dataForTableNode[] = [$sampleProperty, $sampleValue];
        }
        $this->iProvideTheFollowingValidData(new TableNode($dataForTableNode));
        $this->iRequestTheResourceBe('/user', 'created');
        $this->theResponseStatusCodeShouldBe(200);
    }

    /**
     * @Given that user has a verified :mfaType MFA
     */
    public function iGiveThatUserAVerifiedMfa($mfaType)
    {
        $user = User::findOne(['employee_id' => $this->tempEmployeeId]);
        Assert::notEmpty($user, 'Unable to find that user.');
        $mfa = new Mfa([
            'user_id' => $user->id,
            'type' => $mfaType,
            'verified' => 1,
        ]);
        Assert::true($mfa->save(), 'Failed to add that MFA record to the database.');
        
        if ($mfaType === 'backupcode') {
            MfaBackupcode::createBackupCodes($mfa->id, 10);
        }
    }

    /**
     * @Then I should receive :numRecords record(s)
     */
    public function iShouldReceiveRecords($numRecords)
    {
        $this->iShouldReceiveUsers($numRecords);
    }
}
