<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use common\components\Sheets;
use Webmozart\Assert\Assert;

class SheetsUnitTestsContext extends UnitTestsContext
{
    private $users;
    private $header;
    private $table;

    /**
     * @Given I have a list of users
     */
    public function iHaveAListOfUsers()
    {
        $this->users = [
            [
                'employee_id' => '12345',
                'email' => 'user12345@example.com'
            ],
            [
                'employee_id' => '12346',
                'email' => 'user12346@example.com'
            ],
        ];
    }

    /**
     * @Given I have an array of table headers
     */
    public function iHaveAnArrayOfTableHeaders()
    {
        $this->header = [
            'employee_id',
            'email',
            'date',
            'time',
            'datetime',
        ];
    }

    /**
     * @When I generate a table
     */
    public function iGenerateATable()
    {
        $this->table = Sheets::makeTable($this->header, $this->users);
    }

    /**
     * @Then I see that the table was generated correctly
     */
    public function iSeeThatTheTableWasGeneratedCorrectly()
    {
        $table = $this->table;
        $users = $this->users;
        Assert::eq(count($table), 2);
        Assert::eq($table[0][0], $users[0]['employee_id']);
        Assert::eq($table[0][1], $users[0]['email']);
        Assert::eq($table[0][2], date('Y-m-d'));
        Assert::range(strtotime($table[0][3]), time() - 5, time());
        Assert::range(strtotime($table[0][4]), time() - 5, time());

        Assert::eq($table[1][0], $users[1]['employee_id']);
        Assert::eq($table[1][1], $users[1]['email']);
        Assert::eq($table[1][2], date('Y-m-d'));
        Assert::range(strtotime($table[1][3]), time() - 5, time());
        Assert::range(strtotime($table[1][4]), time() - 5, time());
    }
}
