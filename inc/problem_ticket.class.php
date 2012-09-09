<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class Problem_Ticket extends CommonDBRelation{

   // From CommonDBRelation
   static public $itemtype_1 = 'Problem';
   static public $items_id_1 = 'problems_id';

   static public $itemtype_2 = 'Ticket';
   static public $items_id_2 = 'tickets_id';
   static public $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;


   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   static function getTypeName($nb=0) {
      return _n('Link Ticket/Problem','Links Ticket/Problem',$nb);
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
    */
   function getSearchOptions() {
      return parent::getSearchOptions();
   }


   /**
    * Show tickets for a problem
    *
    * @param $problem Problem object
   **/
   static function showForProblem(Problem $problem) {
      global $DB, $CFG_GLPI;

      $ID = $problem->getField('id');
      if (!$problem->can($ID,'r')) {
         return false;
      }

      $canedit = $problem->can($ID,'w');

      $rand = mt_rand();

      $query = "SELECT DISTINCT `glpi_problems_tickets`.`id` AS linkID,
                                `glpi_tickets`.*
                FROM `glpi_problems_tickets`
                LEFT JOIN `glpi_tickets`
                     ON (`glpi_problems_tickets`.`tickets_id` = `glpi_tickets`.`id`)
                WHERE `glpi_problems_tickets`.`problems_id` = '$ID'
                ORDER BY `glpi_tickets`.`name`";
      $result = $DB->query($query);


      $tickets = array();
      $used = array();
      if ($numrows = $DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $tickets[$data['id']] = $data;
            $used[$data['id']] = $data['id'];
         }
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='changeticket_form$rand' id='changeticket_form$rand' method='post'
               action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add a ticket')."</th></tr>";

         echo "<tr class='tab_bg_2'><td class='right'>";
         echo "<input type='hidden' name='problems_id' value='$ID'>";
         Ticket::dropdown(array('used'        => $used,
                                'entity'      => $problem->getEntityID(),
                                'entity_sons' => $problem->isRecursive()));
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\"".__s('Add')."\" class='submit'>";
         echo "</td></tr>";

         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $numrows) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('num_displayed'  => $numrows);
         Html::showMassiveActions(__CLASS__, $massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='11'>".Ticket::getTypeName($numrows)."</th>";
      echo "</tr>";
      if ($numrows) {
         Ticket::commonListHeader(Search::HTML_OUTPUT,'mass'.__CLASS__.$rand);
         Session::initNavigateListItems('Ticket',
                                 //TRANS : %1$s is the itemtype name,
                                 //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'), $problem->getTypeName(1),
                                                $problem->fields["name"]));

         $i = 0;
         foreach ($tickets as $data) {
            Session::addToNavigateListItems('Ticket', $data["id"]);
            Ticket::showShort($data['id'], false, Search::HTML_OUTPUT, $i, $data['linkID']);
            $i++;
         }
      }
      echo "</table>";
      if ($canedit && $numrows) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions(__CLASS__, $massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";



   }


   /**
    * Show problems for a ticket
    *
    * @param $ticket Ticket object
   **/
   static function showForTicket(Ticket $ticket) {
      global $DB, $CFG_GLPI;

      $ID = $ticket->getField('id');
      if (!$ticket->can($ID,'r')) {
         return false;
      }

      $canedit = $ticket->can($ID,'w');

      $rand = mt_rand();
//       echo "<form name='problemticket_form$rand' id='problemticket_form$rand' method='post'
//              action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
//
//       echo "<div class='center'><table class='tab_cadre_fixehov'>";
//       echo "<tr><th colspan='9'>";
//       printf(__('%1$s - %2$s'), _n('Problem', 'Problems', 2),
//              "<a href='".Toolbox::getItemTypeFormURL('Problem')."?tickets_id=$ID'>".
//                __('Create a problem from this ticket')."</a>");
//       echo "</th></tr>";
//       echo "<tr><th colspan='9'>".__('Title')."</th>";
//       echo "</tr>";

      $query = "SELECT DISTINCT `glpi_problems_tickets`.`id` AS linkID,
                                `glpi_problems`.*
                FROM `glpi_problems_tickets`
                LEFT JOIN `glpi_problems`
                     ON (`glpi_problems_tickets`.`problems_id` = `glpi_problems`.`id`)
                WHERE `glpi_problems_tickets`.`tickets_id` = '$ID'
                ORDER BY `glpi_problems`.`name`";
      $result = $DB->query($query);

      $problems = array();
      $used = array();
      if ($numrows = $DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $problems[$data['id']] = $data;
            $used[$data['id']] = $data['id'];
         }
      }
      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='problemticket_form$rand' id='problemticket_form$rand' method='post'
               action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='3'>".__('Add a problem')."</th></tr>";
         echo "<tr class='tab_bg_2'><td>";
         echo "<input type='hidden' name='tickets_id' value='$ID'>";
         Problem::dropdown(array('used'        => $used,
                                'entity'      => $ticket->getEntityID()));
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button','Add')."\" class='submit'>";
         echo "</td><td>";
         echo "<a href='".Toolbox::getItemTypeFormURL('Problem')."?tickets_id=$ID'>";
         _e('Create a problem from this ticket');
         echo "</a>";

         echo "</td></tr></table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $numrows) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('num_displayed'  => $numrows);
         Html::showMassiveActions(__CLASS__, $massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='11'>".Problem::getTypeName($numrows)."</th>";
      echo "</tr>";
      if ($numrows) {
         Problem::commonListHeader(Search::HTML_OUTPUT,'mass'.__CLASS__.$rand);
         Session::initNavigateListItems('Problem',
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $ticket->getTypeName(1), $ticket->fields["name"]));

         $i = 0;
         foreach ($problems as $data) {
            Session::addToNavigateListItems('Problem', $data["id"]);
            Problem::showShort($data['id'], Search::HTML_OUTPUT, $i, $data['linkID']);
            $i++;
         }
      }

      echo "</table>";
      if ($canedit && $numrows) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions(__CLASS__, $massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (Session::haveRight("show_all_problem","1")) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Ticket' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_problems_tickets',
                                             "`tickets_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(self::getTypeName(2), $nb);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Ticket' :
            self::showForTicket($item);
            break;
      }
      return true;
   }

}
?>
