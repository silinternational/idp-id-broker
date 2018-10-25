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
}
