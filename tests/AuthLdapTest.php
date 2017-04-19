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
      $ldap->preconfig('AD');
      $this->assertEquals($ldap->fields['login_field'], 'samaccountname');
      $this->assertEquals($ldap->fields['sync_field'], 'samaccountname');
      $ldap->preconfig('');
      $this->assertEquals($ldap->fields['login_field'], 'uid');
   }

   /**
   * @cover Authldap::prepareInputForUpdate
   */
   public function testPrepareInputForUpdate() {
      $ldap   = new Authldap();
      $input  = ['name' => 'ldap', 'rootdn_passwd' => ''];
      $result = $ldap->prepareInputForUpdate($input); 
   }
}
