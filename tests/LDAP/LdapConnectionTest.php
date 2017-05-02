<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/* Test for inc/authldap.class.php */

class LdapConnectionTest extends DbTestCase {

   protected function setUp() {
      $ldap = new AuthLDAP();
      $id = $ldap->add(['name'          => 'ldap',
                        'host'          => 'ldap',
                        'port'          => '389',
                        'login_field'   => 'uid',
                        'basedn'        => 'dc=glpi,dc=org',
                        'rootdn'        => 'cn=admin,dc=glpi,dc=org',
                        'rootdn_passwd' => 'password',
                        'condition'     => '(objectclass=inetOrgPerson)',
                        'is_active'     => 1,
                        'is_default'    => 1
                     ]);
   }

   protected function tearDown() {
      $ldap   = getItemByTypeName('AuthLDAP', 'ldap');
      $ldap->delete(['id' => $ldap->getID()], true);
   }

   /**
   * @group  ldap
   * @cover LdapConnection::connectToServer
   */
   public function testConnectToServer() {
      $ldap   = getItemByTypeName('AuthLDAP', 'ldap');

      //Anonymous connection
      $result = LdapConnection::connectToServer($ldap->fields['host'],
                                                $ldap->fields['port']);
      $this->assertNotEquals(false, $result);
      LdapConnection::close($result);

      //Connection with a rootdn and password
      $result = LdapConnection::connectToServer($ldap->fields['host'],
                                                $ldap->fields['port'],
                                                $ldap->fields['rootdn'],
                                                Toolbox::decrypt($ldap->fields['rootdn_passwd'], GLPIKEY)
                                                );
      $this->assertNotEquals(false, $result);
      LdapConnection::close($result);

      $result = LdapConnection::connectToServer('foo',
                                                $ldap->fields['port'],
                                                $ldap->fields['rootdn'],
                                                Toolbox::decrypt($ldap->fields['rootdn_passwd'], GLPIKEY)
                                                );
      $this->assertFalse($result);
      LdapConnection::close($result);
   }

   /**
   * @group LDAP
   * @cover LdapConnection::read
   */
   public function testRead() {
      $ldap        = getItemByTypeName('AuthLDAP', 'ldap');
      $connection  = new LdapConnection();

      //Connection with a rootdn and password
      $conn = $connection->connectToServer($ldap->fields['host'],
                                           $ldap->fields['port'],
                                           $ldap->fields['rootdn'],
                                           Toolbox::decrypt($ldap->fields['rootdn_passwd'],
                                                            GLPIKEY)
                                           );
      $this->assertNotEquals(false, $conn);

      $dn     = "ou=groups, ou=usa, ou=ldap2, dc=glpi,dc=org";
      $result = $connection->read($conn,
                                  $dn,
                                  "(objectClass=groupOfNames)", ['cn']);
      var_dump($result);
      LdapConnection::close($conn);

   }
}
