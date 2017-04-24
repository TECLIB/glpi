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

   public function setUp() {
      $ldap = new AuthLDAP();
      $id = $ldap->add(['name'          => 'ldap',
                        'host'          => 'ldap-master',
                        'port'          => '3389',
                        'login_field'   => 'uid',
                        'basedn'        => 'dc=glpi,dc=org',
                        'rootdn'        => 'cn=admin,dc=glpi,dc=org',
                        'rootdn_passwd' => 'password',
                        'condition'     => '(objectclass=inetOrgPerson)',
                        'is_active'     => 1,
                        'is_default'    => 1
                     ]);

      $replicate = new AuthLdapReplicate();
      $replicate->add(['authldaps_id' => $id,
                       'host'         => 'ldap-slave',
                       'port'         => '3390'
                      ]);
   }

   /**
   * @group  ldap
   * @cover LdapConnection::connectToServer
   */
   public function testConnectToServer() {
      $ldap        = getItemByTypeName('AuthLDAP', 'ldap');
      $replicates  = getAllDatasFromTable('glpi_authldapreplicates',
                                          "`authldaps_id`=".$ldap->getID());
      $this->assertEquals(count($replicates), 1);
      $replicate   = current($replicates);

      //Anonymous connection
      $result = LdapConnection::connectToServer($ldap->fields['host'],
                                                $ldap->fields['port']);
      $this->assertNotEquals($result, false);

      //Connection with a rootdn and password
      $result = LdapConnection::connectToServer($ldap->fields['host'],
                                                $ldap->fields['port'],
                                                $ldap->fields['rootdn'],
                                                Toolbox::decrypt($ldap->fields['rootdn_passwd'], GLPIKEY)
                                                );
      $this->assertNotEquals($result, false);

      $result = LdapConnection::connectToServer('foo',
                                                $ldap->fields['port'],
                                                $ldap->fields['rootdn'],
                                                Toolbox::decrypt($ldap->fields['rootdn_passwd'], GLPIKEY)
                                                );
      $this->assertFalse($result);

      //Connection with a rootdn and password to the slave directory
      $result = LdapConnection::connectToServer($replicate['host'],
                                                $replicate['port'],
                                                $ldap->fields['rootdn'],
                                                Toolbox::decrypt($ldap->fields['rootdn_passwd'], GLPIKEY)
                                                );
      $this->assertNotEquals($result, false);

   }
}
