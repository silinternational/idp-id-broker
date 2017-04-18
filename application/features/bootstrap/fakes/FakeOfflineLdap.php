<?php
namespace Sil\SilIdBroker\Behat\Context\fakes;

use common\ldap\Ldap;
use common\ldap\LdapConnectionException;

class FakeOfflineLdap extends Ldap
{
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
