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

class AuthLDAPTest extends DbTestCase {

   private function addLdapServers() {
      $ldap = new AuthLDAP();
      $ldap->add(['name'        => 'LDAP1',
                  'is_active'   => 1,
                  'is_default'  => 0,
                  'basedn'      => 'ou=people,dc=mycompany',
                  'login_field' => 'uid',
                  'phone_field' => 'phonenumber'
                 ]);
      $ldap->add(['name'         => 'LDAP2',
                  'is_active'    => 0,
                  'is_default'   => 0,
                  'basedn'       => 'ou=people,dc=mycompany',
                  'login_field'  => 'uid',
                  'phone_field'  => 'phonenumber',
                  'email1_field' => 'email'
                 ]);
      $ldap->add(['name'        => 'LDAP3',
                  'is_active'   => 1,
                  'is_default'  => 1,
                  'basedn'      => 'ou=people,dc=mycompany',
                  'login_field' => 'email',
                  'phone_field' => 'phonenumber',
                  'email1_field' => 'email'
                 ]);
   }

   /**
   * @cover AuthLDAP::getTypeName
   */
   public function testGetTypeName() {
      $this->assertEquals('LDAP directory', AuthLDAP::getTypeName(1));
      $this->assertEquals('LDAP directories', AuthLDAP::getTypeName(0));
      $this->assertEquals('LDAP directories', AuthLDAP::getTypeName(Session::getPluralNumber()));
   }

   /**
   * @cover Authldap::post_getEmpty
   */
   public function testPost_getEmpty() {
      $ldap = new AuthLDAP();
      $ldap->post_getEmpty();
      $this->assertEquals(count($ldap->fields), 23);
   }

   /**
   * @cover Authldap::unsetUndisclosedFields
   */
   public function testUnsetUndisclosedFields() {
      $fields = ['login_field' => 'test', 'rootdn_passwd' => 'mypassword'];
      AuthLDAP::unsetUndisclosedFields($fields);
      $this->assertFalse(isset($fields['rootdn_passwd']));
   }

   /**
   * @cover Authldap::preconfig
   */
   public function testPreconfig() {
      $ldap = new Authldap();
      //Use Active directory preconfiguration :
      //login_field and sync_field must be filled
      $ldap->preconfig('AD');
      $this->assertEquals($ldap->fields['login_field'], 'samaccountname');
      $this->assertEquals($ldap->fields['sync_field'], 'samaccountname');

      //No preconfiguration model
      $ldap->preconfig('');
      //Login_field is set to uid (default)
      $this->assertEquals($ldap->fields['login_field'], 'uid');
   }

   /**
   * @cover Authldap::prepareInputForUpdate
   */
   public function testPrepareInputForUpdate() {
      $ldap   = new Authldap();

      //------------ Password tests --------------------//
      $input  = ['name' => 'ldap', 'rootdn_passwd' => ''];
      $result = $ldap->prepareInputForUpdate($input);
      //rootdn_passwd is set but empty
      $this->assertFalse(isset($result['rootdn_passwd']));

      //no rootdn_passwd set : should not appear in the response array
      $input  = ['name' => 'ldap'];
      $result = $ldap->prepareInputForUpdate($input);
      $this->assertFalse(isset($result['rootdn_passwd']));

      //rootdn_passwd is set with a value (a password, not encrypted)
      $password = 'toto';
      $input    = ['name' => 'ldap', 'rootdn_passwd' => $password];
      $result   = $ldap->prepareInputForUpdate($input);

      //Expected value to be encrypted using GLPIKEY key
      $expected = Toolbox::encrypt(stripslashes($password), GLPIKEY);
      $this->assertEquals($expected, $result['rootdn_passwd']);

      $password = 'tot\'o';
      $input    = ['name' => 'ldap', 'rootdn_passwd' => $password];
      $result   = $ldap->prepareInputForUpdate($input);

      //Expected value to be encrypted using GLPIKEY key
      $expected = Toolbox::encrypt(stripslashes($password), GLPIKEY);
      $this->assertEquals($expected, $result['rootdn_passwd']);

      $input['_blank_passwd'] = 1;
      $result   = $ldap->prepareInputForUpdate($input);
      $this->assertTrue($result['rootdn_passwd'] == '');

      //Field name finishing with _field : set the value in lower case
      $input['_login_field'] = 'TEST';
      $result         = $ldap->prepareInputForUpdate($input);
      $this->assertTrue($result['_login_field'] == 'test');
   }

   /**
   * @cover AuthLDAP::getGroupSearchTypeName
   */
   public function testgetGroupSearchTypeName() {
      //Get all group search type values
      $search_type = AuthLDAP::getGroupSearchTypeName();
      $this->assertEquals(count($search_type), 3);

      //Give a wrong number value
      $search_type = AuthLDAP::getGroupSearchTypeName(4);
      $this->assertEquals($search_type, NOT_AVAILABLE);

      //Give a wrong string value
      $search_type = AuthLDAP::getGroupSearchTypeName('toto');
      $this->assertEquals($search_type, NOT_AVAILABLE);

      //Give a existing values
      $search_type = AuthLDAP::getGroupSearchTypeName(0);
      $this->assertEquals($search_type, 'In users');

      $search_type = AuthLDAP::getGroupSearchTypeName(1);
      $this->assertEquals($search_type, 'In groups');

      $search_type = AuthLDAP::getGroupSearchTypeName(2);
      $this->assertEquals($search_type, 'In users and groups');
   }

   /**
   * @cover AuthLDAP::getSpecificValueToDisplay
   */
   public function testGetSpecificValueToDisplay() {
      $ldap = new AuthLDAP();

      //Value as an array
      $values = ['group_search_type' => 0];
      $result = $ldap->getSpecificValueToDisplay('group_search_type', $values);
      $this->assertEquals($result, 'In users');

      //Value as a single value
      $values = 1;
      $result = $ldap->getSpecificValueToDisplay('group_search_type', $values);
      $this->assertEquals($result, 'In groups');

      //Value as a single value
      $values = ['name' => 'ldap'];
      $result = $ldap->getSpecificValueToDisplay('name', $values);
      $this->assertEquals($result, '');

   }

   /**
   * @cover AuthLDAP::defineTabs
   */
   public function testDefineTabs() {
      $ldap     = new AuthLDAP();
      $tabs     = $ldap->defineTabs();
      $expected = ['AuthLDAP$main' => 'LDAP directory',
                   'Log$1'         => 'Historical'];
      $this->assertEquals($tabs, $expected);
   }

   /**
   * @cover AuthLDAP::getSearchOptionsNew
   */
   public function testGetSearchOptionsNew() {
      $ldap     = new AuthLDAP();
      $options  = $ldap->getSearchOptionsNew();
      $this->assertEquals(count($options), 31);
   }

   /**
   * @cover AuthLDAP::getSyncFields
   */
   public function testGetSyncFields() {
      $ldap     = new AuthLDAP();
      $values   = ['login_field' => 'value'];
      $result   = $ldap->getSyncFields($values);
      $this->assertEquals(['name' => 'value'], $result);

      $result   = $ldap->getSyncFields([]);
      $this->assertEmpty($result);
   }

   /**
   * @cover AuthLDAP::ldapStamp2UnixStamp
   */
   public function testLdapStamp2UnixStamp() {
      //Good timestamp
      $result = AuthLDAP::ldapStamp2UnixStamp("20161114100339Z");
      $this->assertEquals('1479117819', $result);

      //Bad timestamp format
      $result = AuthLDAP::ldapStamp2UnixStamp("20161114100339");
      $this->assertEquals('', $result);

      //Bad timestamp format
      $result = AuthLDAP::ldapStamp2UnixStamp("201611141003");
      $this->assertEquals('', $result);

   }

   /**
   * @cover AuthLDAP::date2ldapTimeStamp
   */
   public function testDate2ldapTimeStamp() {
      $result = AuthLDAP::date2ldapTimeStamp("2017-01-01 22:35:00");
      $this->assertEquals("20170101223500.0Z", $result);

      //Bad date => 01/01/1970
      $result = AuthLDAP::date2ldapTimeStamp("2017-25-25 22:35:00");
      $this->assertEquals("19700101000000.0Z", $result);

   }

   /**
   * @cover AuthLDAP::dnExistsInLdap
   */
   public function testDnExistsInLdap() {
      $ldap_infos = [ ['uid'      => 'jdoe',
                       'cn'       => 'John Doe',
                       'user_dn'  => 'uid=jdoe, ou=people, dc=mycompany'
                      ],
                      ['uid'      => 'asmith',
                       'cn'       => 'Agent Smith',
                       'user_dn'  => 'uid=asmith, ou=people, dc=mycompany'
                      ]
                    ];

      //Ask for a non existing user_dn : result is false
      $this->assertFalse(AuthLDAP::dnExistsInLdap($ldap_infos, 'uid=jdupont, ou=people, dc=mycompany'));

      //Ask for an dn that exists : result is the user's infos as an array
      $result = AuthLDAP::dnExistsInLdap($ldap_infos, 'uid=jdoe, ou=people, dc=mycompany');
      $this->assertNotEmpty($result);
      $this->assertEquals(count($result), 3);

   }

   /**
   * @cover AuthLDAP::getAllGroups
   */
   public function testGetAllGroups() {
      //TODO
   }

   /**
   * @cover AuthLDAP::getGroupCNByDn
   */
   public function testGetGroupCNByDn() {
      //TODO
   }

   /**
   * @cover AuthLDAP::getGroupsFromLDAP
   */
   public function testGetGroupsFromLDAP() {
      //TODO
   }

   /**
   * @cover AuthLDAP::getLdapServers
   */
   public function testGetLdapServers() {
      $this->addLdapServers();

      //The list of ldap server show the default server in first position
      $result = AuthLDAP::getLdapServers();
      $this->assertEquals(count($result), 3);
      $this->assertEquals(current($result)['name'], 'LDAP3');
   }

   /**
   * @cover AuthLDAP::useAuthLdap
   */
   public function testUseAuthLdap() {
      global $DB;
      $this->addLdapServers();

      $this->assertTrue(AuthLDAP::useAuthLdap());
      $sql = "UPDATE `glpi_authldaps` SET `is_active`='0'";
      $DB->query($sql);
      $this->assertFalse(AuthLDAP::useAuthLdap());
   }

   /**
   * @cover AuthLDAP::getNumberOfServers
   */
   public function testGetNumberOfServers() {
      global $DB;
      $this->addLdapServers();

      $this->assertEquals(AuthLDAP::getNumberOfServers(), 2);
      $sql = "UPDATE `glpi_authldaps` SET `is_active`='0'";
      $DB->query($sql);
      $this->assertEquals(AuthLDAP::getNumberOfServers(), 0);
   }

   /**
   * @cover AuthLDAP::buildLdapFilter
   */
   public function testBuildLdapFilter() {
      $this->addLdapServers();

      $ldap = getItemByTypeName('AuthLDAP', 'LDAP3');
      $result = AuthLDAP::buildLdapFilter($ldap);
      $this->assertEquals($result, "(& (email=*) )");

      $_SESSION['ldap_import']['interface'] = AuthLDAP::SIMPLE_INTERFACE;
      $_SESSION['ldap_import']['criterias'] = ['name'        => 'foo',
                                               'phone_field' => '+33454968584'];
      $result = AuthLDAP::buildLdapFilter($ldap);
      $this->assertEquals($result, '(& (LDAP3=*foo*)(phonenumber=*+33454968584*) )');

      $_SESSION['ldap_import']['criterias']['name'] = '^foo';
      $result = AuthLDAP::buildLdapFilter($ldap);
      $this->assertEquals($result, '(& (LDAP3=foo*)(phonenumber=*+33454968584*) )');

      $_SESSION['ldap_import']['criterias']['name'] = 'foo$';
      $result = AuthLDAP::buildLdapFilter($ldap);
      $this->assertEquals($result, '(& (LDAP3=*foo)(phonenumber=*+33454968584*) )');

      $_SESSION['ldap_import']['criterias']['name'] = '^foo$';
      $result = AuthLDAP::buildLdapFilter($ldap);
      $this->assertEquals($result, '(& (LDAP3=foo)(phonenumber=*+33454968584*) )');

      $_SESSION['ldap_import']['criterias'] = ['name' => '^foo$'];
      $auth->fields['condition'] = '(objectclass=inetOrgPerson)';
      $result = AuthLDAP::buildLdapFilter($ldap);
      $this->assertEquals($result, '(& (LDAP3=foo) )');

      $_SESSION['ldap_import']['begin_date']        = '2017-04-20 00:00:00';
      $_SESSION['ldap_import']['end_date']          = '2017-04-22 00:00:00';
      $_SESSION['ldap_import']['criterias']['name'] = '^foo$';
      $result = AuthLDAP::buildLdapFilter($ldap);
      $this->assertEquals($result,
                          '(& (LDAP3=foo)(modifyTimestamp>=20170420000000.0Z)(modifyTimestamp<=20170422000000.0Z) )');
   }

   /**
   * @cover AuthLDAP::addTimestampRestrictions
   */
   public function testAddTimestampRestrictions() {
      $result = AuthLDAP::addTimestampRestrictions('',
                                                   '2017-04-22 00:00:00');
      $this->assertEquals($result, "(modifyTimestamp<=20170422000000.0Z)");

      $result = AuthLDAP::addTimestampRestrictions('2017-04-20 00:00:00',
                                                   '');
      $this->assertEquals($result, "(modifyTimestamp>=20170420000000.0Z)");

      $result = AuthLDAP::addTimestampRestrictions('',
                                                   '');
      $this->assertEquals($result, '');

      $result = AuthLDAP::addTimestampRestrictions('2017-04-20 00:00:00',
                                                   '2017-04-22 00:00:00');
      $this->assertEquals($result, "(modifyTimestamp>=20170420000000.0Z)(modifyTimestamp<=20170422000000.0Z)");
   }

   /**
   * @cover AuthLDAP::getDefault
   */
   public function testGetDefault() {
      //No default server defined : return 0
      $this->assertEquals(AuthLDAP::getDefault(), 0);

      //Load ldap servers
      $this->addLdapServers();
      $ldap = getItemByTypeName('AuthLDAP', 'LDAP3');
      $this->assertEquals(AuthLDAP::getDefault(), $ldap->getID());
   }

   /**
   * @cover AuthLDAP::post_updateItem
   */
   public function testPost_updateItem() {
      //Load ldap servers
      $this->addLdapServers();

      //Get first lDAP server
      $ldap = getItemByTypeName('AuthLDAP', 'LDAP1');

      //Set it as default server
      $ldap->update(['id' => $ldap->getID(), 'is_default' => 1]);

      //Get first lDAP server now
      $ldap = getItemByTypeName('AuthLDAP', 'LDAP1');
      $this->assertEquals($ldap->fields['is_default'], 1);

      //Get third ldap server (former default one)
      $ldap = getItemByTypeName('AuthLDAP', 'LDAP3');
      //Check that it's not the default server anymore
      $this->assertEquals($ldap->fields['is_default'], 0);
   }

   /**
   * @cover AuthLDAP::post_updateItem
   */
   public function testPost_addItem() {
      //Load ldap servers
      $this->addLdapServers();

      $ldap     = new AuthLDAP();
      $ldaps_id = $ldap->add(['name'        => 'LDAP4',
                              'is_active'   => 1,
                              'is_default'  => 1,
                              'basedn'      => 'ou=people,dc=mycompany',
                              'login_field' => 'email',
                              'phone_field' => 'phonenumber'
                             ]);
      $ldap->getFromDB($ldaps_id);
      $this->assertEquals($ldap->fields['is_default'], 1);

      //Get third ldap server (former default one)
      $ldap = getItemByTypeName('AuthLDAP', 'LDAP3');
      //Check that it's not the default server anymore
      $this->assertEquals($ldap->fields['is_default'], 0);
   }

   /**
   * @cover AuthLDAP::prepareInputForAdd
   */
   public function testPrepareInputForAdd() {
      $ldap     = new AuthLDAP();

      //Create a server : as it's the first, it's the default one
      $ldaps_id = $ldap->add(['name'        => 'LDAP1',
                              'is_active'   => 1,
                              'basedn'      => 'ou=people,dc=mycompany',
                              'login_field' => 'email',
                              'rootdn_passwd' => 'password'
                             ]);
      $ldap->getFromDB($ldaps_id);
      $this->assertEquals($ldap->fields['is_default'], 1);
      $this->assertNotEquals($ldap->fields['rootdn_passwd'], 'password');

   }

   /**
   * @cover AuthLDAP::getServersWithImportByEmailActive
   */
   public function testGetServersWithImportByEmailActive() {
      //No server, method return should be an empty array
      $result = AuthLDAP::getServersWithImportByEmailActive();
      $this->assertEmpty($result);

      $this->addLdapServers();

      //Return one ldap server : because LDAP2 is disabled
      $result = AuthLDAP::getServersWithImportByEmailActive();
      $this->assertEquals(count($result), 1);

      //Enable LDAP2
      $ldap = getItemByTypeName('AuthLDAP', 'LDAP2');
      $ldap->update(['id' => $ldap->getID(), 'is_active' => 1]);

      //Now there should be 2 enabled servers
      $result = AuthLDAP::getServersWithImportByEmailActive();
      $this->assertEquals(count($result), 2);
   }

   /**
   * @cover AuthLDAP::getTabNameForItem
   */
   public function testgetTabNameForItem() {
      $this->Login();
      $this->addLdapServers();

      $ldap   = getItemByTypeName('AuthLDAP', 'LDAP1');
      $result = $ldap->getTabNameForItem($ldap);
      $expected = [1 => 'Test',
                   2 => 'Users',
                   3 => 'Groups',
                   5 => 'Advanced information',
                   6 => 'Replicates'
                  ];
      $this->assertEquals($result, $expected);

      $result = $ldap->getTabNameForItem($ldap, 1);
      $this->assertEquals($result, '');
   }

   /**
   * @cover AuthLDAP::getAllReplicateForAMaster
   */
   public function testGetAllReplicateForAMaster() {
      $ldap      = new AuthLDAP();
      $replicate = new AuthLdapReplicate();

      $ldaps_id = $ldap->add(['name'        => 'LDAP1',
                              'is_active'   => 1,
                              'is_default'  => 0,
                              'basedn'      => 'ou=people,dc=mycompany',
                              'login_field' => 'uid',
                              'phone_field' => 'phonenumber'
                             ]);
      $replicate->add(['name'         => 'replicate1',
                       'host'         => 'myhost1',
                       'port'         => 3306,
                       'authldaps_id' => $ldaps_id]);
      $replicate->add(['name'         => 'replicate2',
                       'host'         => 'myhost1',
                       'port'         => 3306,
                       'authldaps_id' => $ldaps_id]);
      $replicate->add(['name'         => 'replicate3',
                       'host'         => 'myhost1',
                       'port'         => 3306,
                       'authldaps_id' => $ldaps_id]);

      $result = $ldap->getAllReplicateForAMaster($ldaps_id);
      $this->assertEquals(count($result), 3);

      $result = $ldap->getAllReplicateForAMaster(100);
      $this->assertEquals(count($result), 0);
   }
}
