<?php
use yii\helpers\Html as yHtml;

/**
 * @var string $contactName
 * @var string $bestPracticeUrl
 * @var string $deactivateInstructionsUrl
 * @var array  $users
 * @var string $emailSignature
 */
?>
<p>Dear <?= yHtml::encode($contactName) ?>,</p>

<p>
    As GTIS works towards securing SIL's accounts, we are auditing SIL Identity access and SIL Google accounts and
    asking HR to consider deactivating accounts that haven't been used in more than 6 months.
</p>
<p>
    Identity accounts are used to gain access to Workday, REAP, and Gateway. Often, when an account is not used,
    the staff member uses the Identity from their sending organization. 
</p>
<p>
    Google accounts are used for email and Drive.
</p>
<p>
    <?= yHtml::a(yHtml::encode($bestPracticeUrl), "Link to Best Practice") ?>
</p>
<p>
    Go here for instructions on how to change access and deactivate email accounts:
</p>
<p>
    <?= yHtml::a(yHtml::encode($deactivateInstructionsUrl), yHtml::encode($deactivateInstructionsUrl)) ?>
</p>
<p>
    Here is a list of Staff IDs, Usernames and/or Email Addresses, and last login date. Please deactivate those
    you decide are unreasonable. If, in the future, they need to be reactivated, you can do so by re-checking the
    box in the System Access area of the person's profile in Workday. 
</p>
<h1>Unused SIL Identity Accounts</h1>
<table>
    <tr>
        <th>Staff Id</th>
        <th>Username</th>
        <th>Last IdP Login</th>
    </tr>
    <? foreach ($users as $user): ?>
    <tr>
        <td><?= $user['uuid'] ?></td>
        <td><?= $user['username'] ?></td>
        <td><?= $user['last_login_utc'] ?></td>
    </tr>
    <? endforeach; ?>
</table>

<p>Thanks,</p>

<p><i><?= nl2br(yHtml::encode($emailSignature), false) ?></i></p>
