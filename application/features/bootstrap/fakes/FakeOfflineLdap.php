<?php
namespace Sil\SilIdBroker\Behat\Context\fakes;

use common\ldap\Ldap;
use common\ldap\LdapConnectionException;
use Sil\Psr3Adapters\Psr3ConsoleLogger;

class FakeOfflineLdap extends Ldap
{
    public function init()
    {
        parent::init();
        $this->logger = new Psr3ConsoleLogger();
    }
    /**
     * Connect to the LDAP (if not yet connected).
     * 
     * NOTE: This WILL fail (and throw an exception).
     *
     * @throws LdapConnectionException
     */
    public function connect()
    {
        throw new LdapConnectionException('FAKE: LDAP is offline');
    }
}
