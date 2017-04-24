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

class AuthLDAPReplicateTest extends DbTestCase {

   /**
   * @cover AuthLdapReplicate::canCreate
   */
   public function testCanCreate() {
      $this->Login();
      $this->assertEquals(AuthLdapReplicate::canCreate(), 2);

      $_SESSION['glpiactiveprofile']['config'] = READ;
      $this->assertEquals(AuthLdapReplicate::canCreate(), 0);

      $_SESSION['glpiactiveprofile']['config'] = 0;
      $this->assertEquals(AuthLdapReplicate::canCreate(), 0);
   }

   /**
   * @cover AuthLdapReplicate::canPurge
   */
   public function testCanPurge() {
      $this->Login();
      $this->assertEquals(AuthLdapReplicate::canPurge(), 2);

      $_SESSION['glpiactiveprofile']['config'] = READ;
      $this->assertEquals(AuthLdapReplicate::canCreate(), 0);

      $_SESSION['glpiactiveprofile']['config'] = 0;
      $this->assertEquals(AuthLdapReplicate::canCreate(), 0);
   }

   /**
   * @cover AuthLdapReplicate::getForbiddenStandardMassiveAction
   */
   public function testGetForbiddenStandardMassiveAction() {
      $this->Login();
      $replicate = new AuthLdapReplicate();
      $result    = $replicate->getForbiddenStandardMassiveAction();
      $this->assertEquals($result, [0 => 'update']);
   }

   /**
   * @cover AuthLdapReplicate::prepareInputForAdd
   */
   public function testPrepareInputForAdd() {
      $replicate = new AuthLdapReplicate();
      //Do not set a port : no port added
      $result = $replicate->prepareInputForAdd(['name' => 'test',
                                                'host' => 'host'
                                               ]);
      $this->assertFalse(isset($result['port']));

      //Port=0, change value to 389
      $result = $replicate->prepareInputForAdd(['name' => 'test',
                                                'host' => 'host',
                                                'port' => 0
                                               ]);
      $this->assertEquals($result['port'], 389);

      //Port set : do not change it's value
      $result = $replicate->prepareInputForAdd(['name' => 'test',
                                                'host' => 'host',
                                                'port' => 3389
                                               ]);
      $this->assertEquals($result['port'], 3389);
   }

   /**
   * @cover AuthLdapReplicate::prepareInputForUpdate
   */
   public function testPrepareInputForUpdate() {
      $replicate = new AuthLdapReplicate();
      //Do not set a port : no port added
      $result = $replicate->prepareInputForUpdate(['name' => 'test',
                                                   'host' => 'host'
                                                 ]);
      $this->assertFalse(isset($result['port']));

      //Port=0, change value to 389
      $result = $replicate->prepareInputForUpdate(['name' => 'test',
                                                   'host' => 'host',
                                                   'port' => 0
                                                  ]);
      $this->assertEquals($result['port'], 389);

      //Port set : do not change it's value
      $result = $replicate->prepareInputForUpdate(['name' => 'test',
                                                   'host' => 'host',
                                                   'port' => 3389
                                                  ]);
      $this->assertEquals($result['port'], 3389);
   }

}
