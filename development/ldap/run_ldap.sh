#!/usr/bin/env bash

set -e

mkdir -p /var/lib/ldap/accesslog 
chown ldap:ldap /var/lib/ldap/accesslog  

chown ldap:ldap /etc/openldap/certs/ldap.*
chmod 600 /etc/openldap/certs/ldap.*

exec /usr/sbin/slapd -h "ldap:/// ldaps:/// ldapi:///" -u ldap -d $DEBUG_LEVEL -f /etc/openldap/slapd.conf
