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

// Relation between Contracts and Items
class Contract_Item extends CommonDBRelation{

   // From CommonDBRelation
   static public $itemtype_1 = 'Contract';
   static public $items_id_1 = 'contracts_id';

   static public $itemtype_2 = 'itemtype';
   static public $items_id_2 = 'items_id';


   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * Check right on an contract - overloaded to check max_links_allowed
    *
    * @param $ID              ID of the item (-1 if new item)
    * @param $right           Right to check : r / w / recursive
    * @param &$input    array of input data (used for adding item) (default NULL)
    *
    * @return boolean
   **/
   function can($ID, $right, array &$input=NULL) {

      if ($ID < 0) {
         // Ajout
         $contract = new Contract();

         if (!$contract->getFromDB($input['contracts_id'])) {
            return false;
         }
         if ($contract->fields['max_links_allowed'] > 0
             && countElementsInTable($this->getTable(),
                                     "`contracts_id`='".$input['contracts_id']."'")
                                       >= $contract->fields['max_links_allowed']) {
               return false;
         }
      }
      return parent::can($ID,$right,$input);
   }


   static function getTypeName($nb=0) {
      return _n('Link Contract/Item','Links Contract/Item',$nb);
   }

   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'items_id':
            if (isset($values['itemtype'])) {
               if (isset($options['comments']) && $options['comments']) {
                  $tmp = Dropdown::getDropdownName(getTableForItemtype($values['itemtype']),
                                                   $values[$field], 1);
                  return sprintf(__('%1$s %2$s'), $tmp['name'],
                                 Html::showToolTip($tmp['comment'], array('display' => false)));

               }
               return Dropdown::getDropdownName(getTableForItemtype($values['itemtype']),
                                                $values[$field]);
            }
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   static function getSpecificValueToSelect($field, $name='', $values = '', array $options=array()) {
      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;
      switch ($field) {
         case 'items_id' :
            if (isset($values['itemtype']) && !empty($values['itemtype'])) {
               $options['name']  = $name;
               $options['value'] = $values[$field];
               return Dropdown::show($values['itemtype'], $options);
            }
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   function getSearchOptions() {

      $tab                     = array();

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = __('ID');
      $tab[2]['massiveaction'] = false;
      $tab[2]['datatype']        = 'number';

      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'items_id';
      $tab[3]['name']          = __('Associated item ID');
      $tab[3]['massiveaction'] = false;
      $tab[3]['datatype']      = 'specific';
      $tab[3]['additionalfields'] = array('itemtype');

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'itemtype';
      $tab[4]['name']          = __('Type');
      $tab[4]['massiveaction'] = false;
      $tab[4]['datatype']      = 'itemtypename';
      $tab[4]['itemtype_list'] = 'contract_types';

      return $tab;
   }


   /**
    * @pram $item    CommonDBTM object
   **/
   static function countForItem(CommonDBTM $item) {

      return countElementsInTable('glpi_contracts_items',
                                  "`itemtype` = '".$item->getType()."'
                                   AND `items_id` ='".$item->getField('id')."'");
   }


   /**
    * @param $item   Contract object
   **/
   static function countForContract(Contract $item) {

      $restrict = "`glpi_contracts_items`.`contracts_id` = '".$item->getField('id')."'";

      return countElementsInTable(array('glpi_contracts_items'), $restrict);
   }


   /**
    * @see inc/CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $CFG_GLPI;

      // Can exists on template
      if (Session::haveRight("contract","r")) {
         switch ($item->getType()) {
            case 'Contract' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(_n('Item', 'Items', 2), self::countForContract($item));
               }
               return _n('Item', 'Items', 2);

            default :
               if ($_SESSION['glpishow_count_on_tabs']
                   && in_array($item->getType(), $CFG_GLPI["contract_types"])) {
                  return self::createTabEntry(Contract::getTypeName(2), self::countForItem($item));
               }
               return _n('Contract', 'Contracts', 2);

         }
      }
      return '';
   }


   /**
    * @param $item         CommonGLPI object
    * @param $tabnum       (default 1)
    * @param $withtemplate (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      switch ($item->getType()) {
         case 'Contract' :
            self::showForContract($item);

         default :
            if (in_array($item->getType(), $CFG_GLPI["contract_types"])) {
               Contract::showAssociated($item, $withtemplate);
            }
      }
      return true;
   }


   /**
    * Duplicate contracts from an item template to its clone
    *
    * @since version 0.84
    *
    * @param $itemtype     itemtype of the item
    * @param $oldid        ID of the item to clone
    * @param $newid        ID of the item cloned
    * @param $newitemtype  itemtype of the new item (= $itemtype if empty) (default '')
   **/
   static function cloneItem($itemtype, $oldid, $newid, $newitemtype='') {
      global $DB;

      if (empty($newitemtype)) {
         $newitemtype = $itemtype;
      }

      $query  = "SELECT `contracts_id`
                 FROM `glpi_contracts_items`
                 WHERE `items_id` = '$oldid'
                        AND `itemtype` = '$itemtype';";

      foreach ($DB->request($query) as $data) {
         $contractitem = new self();
         $contractitem->add(array('contracts_id' => $data["contracts_id"],
                                  'itemtype'     => $newitemtype,
                                  'items_id'     => $newid));
      }
   }


   /**
    * Print the HTML array for Items linked to current contract
    *
    *@return Nothing (display)
    **/
   static function showForContract(Contract $contract) {
      global $DB, $CFG_GLPI;

      $instID = $contract->fields['id'];

      if (!$contract->can($instID,'r')) {
         return false;
      }
      $canedit = $contract->can($instID,'w');
      $rand    = mt_rand();

      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_contracts_items`
                WHERE `glpi_contracts_items`.`contracts_id` = '$instID'
                ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      $data = array();
      $totalnb = 0;
      for ($i=0 ; $i<$number ; $i++) {
         $itemtype = $DB->result($result, $i, "itemtype");
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if ($item->canView()) {
            $itemtable = getTableForItemType($itemtype);
            $query     = "SELECT `$itemtable`.*,
                                 `glpi_contracts_items`.`id` AS IDD,
                                 `glpi_entities`.`id` AS entity
                          FROM `glpi_contracts_items`,
                               `$itemtable`";
            if ($itemtype != 'Entity') {
               $query .= " LEFT JOIN `glpi_entities`
                                 ON (`$itemtable`.`entities_id`=`glpi_entities`.`id`) ";
            }
            $query .= " WHERE `$itemtable`.`id` = `glpi_contracts_items`.`items_id`
                              AND `glpi_contracts_items`.`itemtype` = '$itemtype'
                              AND `glpi_contracts_items`.`contracts_id` = '$instID'";

            if ($item->maybeTemplate()) {
               $query .= " AND `$itemtable`.`is_template` = '0'";
            }
            $query .= getEntitiesRestrictRequest(" AND",$itemtable, '', '',
                                                 $item->maybeRecursive())."
                      ORDER BY `glpi_entities`.`completename`, `$itemtable`.`name`";

            $result_linked = $DB->query($query);
            $nb            = $DB->numrows($result_linked);

            if ($nb > $_SESSION['glpilist_limit']) {
               $link = "<a href='". Toolbox::getItemTypeSearchURL($itemtype) . "?" .
                     rawurlencode("contains[0]") . "=" . rawurlencode('$$$$'.$instID) . "&amp;" .
                     rawurlencode("field[0]") . "=29&amp;sort=80&amp;order=ASC&amp;is_deleted=0".
                     "&amp;start=0". "'>" . __('Device list')."</a>";

               $data[$itemtype] = array('longlist' => true,
                                        'name' => sprintf(__('%1$s: %2$s'), $item->getTypeName($nb), $nb),
                                        'link' => $link);
            } else if ($nb > 0) {
               for ($prem=true ; $objdata=$DB->fetch_assoc($result_linked) ; $prem=false) {
                  $data[$itemtype][$objdata['id']] = $objdata;
               }
            }
            $totalnb += $nb;
         }
      }

      if ($canedit
         && (($contract->fields['max_links_allowed'] == 0)
            || ($contract->fields['max_links_allowed'] > $totalnb))) {
         echo "<div class='firstbloc'>";
         echo "<form name='contract_form$rand' id='contract_form$rand' method='post'
               action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add an item')."</th></tr>";

         echo "<tr class='tab_bg_1'><td class='right'>";
         Dropdown::showAllItems("items_id", 0, 0,
                                ($contract->fields['is_recursive']?-1:$contract->fields['entities_id']),
                                $CFG_GLPI["contract_types"], false, true);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "<input type='hidden' name='contracts_id' value='$instID'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $totalnb) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array();
         Html::showMassiveActions(__CLASS__, $massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";
       echo "<tr>";

      if ($canedit && $totalnb) {
         echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
      }
      echo "<th>".__('Type')."</th>";
      echo "<th>".__('Entity')."</th>";
      echo "<th>".__('Name')."</th>";
      echo "<th>".__('Serial number')."</th>";
      echo "<th>".__('Inventory number')."</th>";
      echo "<th>".__('Status')."</th>";
      echo "</tr>";

      $totalnb = 0;
      foreach ($data as $itemtype => $datas) {

         if (isset($datas['longlist'])) {
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
               echo "<td>&nbsp;</td>";
            }
            //TRANS: %1$s is a type name, %2$s is a number
            echo "<td class='center'>".$datas['name']."</td>";
            echo "<td class='center' colspan='2'>";
            echo $datas['link']."</td>";
            echo "<td class='center'>-</td><td class='center'>-</td></tr>";

         } else {
            $prem = true;
            $nb = count($datas);
            foreach ($datas as $id => $objdata) {
               $name = $objdata["name"];
               if ($_SESSION["glpiis_ids_visible"]
                     || empty($objdata["name"])) {
                  $name = " (".$objdata["id"].")";
               }
               $link = Toolbox::getItemTypeFormURL($itemtype);
               $name = "<a href=\"".$link."?id=".$objdata["id"]."\">".$name."</a>";

               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td width='10'>";
                  echo "<input type='checkbox' name='item[".$objdata["IDD"]."]' value='1'></td>";
               }
               if ($prem) {
                  $item = new $itemtype();
                  $typename = $item->getTypeName($nb);
                  echo "<td class='center top' rowspan='$nb'>".
                           ($nb  >1 ? sprintf(__('%1$s: %2$s'), $typename, $nb): $typename)."</td>";
                  $prem = false;
               }
               echo "<td class='center'>";
               echo Dropdown::getDropdownName("glpi_entities",$objdata['entity'])."</td>";
               echo "<td class='center".
                        (isset($objdata['is_deleted']) && $objdata['is_deleted'] ? " tab_bg_2_2'" : "'");
               echo ">".$name."</td>";
               echo "<td class='center'>".
                        (isset($objdata["serial"])? "".$objdata["serial"]."" :"-")."</td>";
               echo "<td class='center'>".
                        (isset($objdata["otherserial"])? "".$objdata["otherserial"]."" :"-")."</td>";
               echo "<td class='center'>";
               if (isset($objdata["states_id"])) {
                  echo Dropdown::getDropdownName("glpi_states", $objdata['states_id']);
               } else {
                  echo '&nbsp;';
               }
               echo "</td></tr>";

            }
         }
      }
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' colspan='2'>".
            ($totalnb > 0 ? sprintf(__('%1$s = %2$s'), __('Total'), $totalnb) : "&nbsp;");
      echo "</td><td colspan='5'>&nbsp;</td></tr> ";

      echo "</table>";
      if ($canedit && $number) {
         $paramsma['ontop'] =false;
         Html::showMassiveActions(__CLASS__, $paramsma);
         Html::closeForm();
      }
      echo "</div>";
   }

}
?>