<?php
namespace common\ldap;

use Adldap\Adldap;
use Adldap\Connections\Provider;
use Adldap\Exceptions\Auth\BindException;
use Adldap\Exceptions\Auth\PasswordRequiredException;
use Adldap\Exceptions\Auth\UsernameRequiredException;
use Adldap\Schemas\OpenLDAP;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use yii\base\Component;

class Ldap extends Component
{
    private $errors = [];
    private $provider = null;
    
    public $acct_suffix;
    public $domain_controllers;
    public $base_dn;
    public $admin_username;
    public $admin_password;
    
    /** @var LoggerInterface */
    public $logger;
    
    public $use_ssl = true;
    public $use_tls = true;
    public $timeout = 5;
    
    public function init()
    {
        if (empty($this->logger)) {
            $this->logger = new NullLogger();
        }
        
        if ($this->use_ssl && $this->use_tls) {
            // Prefer TLS over SSL.
            $this->use_ssl = false;
        }
        
        if (empty($this->domain_controllers) || empty(join('', $this->domain_controllers))) {
            $this->logger->critical('No domain_controllers provided.');
            throw new \InvalidArgumentException('No domain_controllers provided.');
        }
        
        parent::init();
    }
    
    /**
     * Connect to the LDAP (if not yet connected).
     *
     * @throws LdapConnectionException
     */
    public function connect()
    {
        if ($this->provider === null) {
            $schema = new OpenLDAP();
            $provider = new Provider([
                'acct_suffix' => $this->acct_suffix,
                'domain_controllers' => $this->domain_controllers,
                'base_dn' => $this->base_dn,
                'admin_username' => $this->admin_username,
                'admin_password' => $this->admin_password,
                'use_ssl' => $this->use_ssl,
                'use_tls' => $this->use_tls,
                'timeout' => $this->timeout,
            ], null, $schema);
            $ldapClient = new Adldap();
            $ldapClient->addProvider('default', $provider);
            
            try {
                $ldapClient->connect('default');
                $this->logger->info('Connected to LDAP server.');
                $this->provider = $provider;
            } catch (BindException $e) {
                $errorMessage = sprintf(
                    'There was a problem connecting to the LDAP server: (%s) %s',
                    $e->getCode(),
                    $e->getMessage()
                );
                $this->logger->critical($errorMessage);
                throw new LdapConnectionException($errorMessage, 1481752312, $e);
            }
        }
    }
    
    protected function addError($errorMessage)
    {
        $this->logger->error($errorMessage);
        $this->errors[] = $errorMessage;
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Look for an LDAP User record with the given CN value. If found, return
     * it. Otherwise return null.
     *
     * @param string $userCn The CN value to search for.
     * @return \Adldap\Models\User|null The LDAP User record, or null.
     */
    protected function getUserByCn($userCn)
    {
        $this->connect();
        $results = $this->provider->search()->select([
            'mail', // Email address
            'givenname', // First name
            'sn', // Last name
            'employeenumber', // Employee ID
            'dn', // Distinguished name
        ])->where(['cn' => $userCn])->get();
        foreach ($results as $ldapUser) {
            $this->logger->info('Found user: ' . var_export($userCn, true));
            /* @var $ldapUser \Adldap\Models\User */
            return $ldapUser;
        }
        $this->logger->info('No such user found: ' . var_export($userCn, true));
        return null;
    }
    
    /**
     * See if the given credentials are correct (according to the LDAP).
     *
     * @param string $userCn
     * @param string $password
     * @return boolean
     */
    public function isPasswordCorrectForUser($userCn, $password)
    {
        try {
            $ldapUser = $this->getUserByCn($userCn);
            if ($ldapUser === null) {
                return false;
            }
            return $this->provider->auth()->attempt($ldapUser->dn, $password);
        } catch (UsernameRequiredException $e) {
            return false;
        } catch (PasswordRequiredException $e) {
            return false;
        }
    }
    
    /**
     * Determine whether the specified user exists in the LDAP.
     *
     * @param string $userCn The CN attribute value to match against.
     * @return bool Whether the user exists.
     */
    public function userExists($userCn)
    {
        $ldapUser = $this->getUserByCn($userCn);
        return ((! empty($ldapUser)) && $ldapUser->exists);
    }
}
