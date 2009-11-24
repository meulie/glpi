<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/**
 * Print out a link to add directly a new cartridge from a cartridge item.
 *
 * Print out the link witch make a new cartridge from cartridge item idetified by $ID
 *
 *@param $ID Cartridge type identifier.
 *
 *
 *@return Nothing (displays)
 **/
function showCartridgesAdd($ID) {
   global $CFG_GLPI,$LANG;

   if (!haveRight("cartridge","w")) {
      return false;
   }
   if ($ID > 0) {
      echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/cartridge.edit.php\">";
      echo "<div class='center'>&nbsp;<table class='tab_cadre_fixe'>";
      echo "<tr>";
      echo "<td class='center' class='tab_bg_2'>";
      echo "<input type='submit' name='add_several' value=\"".$LANG['buttons'][8]."\" class='submit'>";
      echo "<input type='hidden' name='tID' value=\"$ID\">\n";
      echo "&nbsp;&nbsp;";
      dropdownInteger('to_add',1,1,100);
      echo "&nbsp;&nbsp;".$LANG['cartridges'][16];
      echo "</td></tr>";
      echo "</table></div>";
      echo "</form><br>";
   }
}

/**
 * Print out the cartridges of a defined type
 *
 * Print out all the cartridges that are issued from the cartridge type identified by $ID
 *
 *@param $tID integer : Cartridge type identifier.
 *@param $show_old boolean : show old cartridges or not.
 *
 *@return Nothing (displays)
 **/
function showCartridges ($tID,$show_old=0) {
   global $DB,$CFG_GLPI,$LANG;

   if (!haveRight("cartridge","r")) {
      return false;
   }
   if ($tID > 0) {
      $canedit=haveRight("cartridge","w");

      $query = "SELECT count(*) AS COUNT
                FROM `glpi_cartridges`
                WHERE (`cartridgeitems_id` = '$tID')";

      if ($result = $DB->query($query)) {
         if ($DB->result($result,0,0)!=0) {
            $total=$DB->result($result, 0, "COUNT");
            $unused=getUnusedCartridgesNumber($tID);
            $used=getUsedCartridgesNumber($tID);
            $old=getOldCartridgesNumber($tID);

            echo "<br><div class='center'><table class='tab_cadre_fixe'>";
            if (!$show_old) {
               echo "<tr><th colspan='7'>";
               echo countCartridges($tID,-1);
               echo "</th>";
               echo "<th colspan='2'>";
               echo "&nbsp;</th></tr>";
            } else { // Old
               echo "<tr><th colspan='8'>";
               echo $LANG['cartridges'][35];
               echo "</th>";
               echo "<th colspan='2'>";
               echo "&nbsp;</th></tr>";
            }
            $i=0;
            echo "<tr><th>".$LANG['common'][2]."</th><th>".$LANG['consumables'][23]."</th>";
            echo "<th>".$LANG['cartridges'][24]."</th><th>".$LANG['consumables'][26]."</th>";
            echo "<th>".$LANG['cartridges'][27]."</th><th>".$LANG['search'][9]."</th>";

            if ($show_old) {
               echo "<th>".$LANG['cartridges'][39]."</th>";
            }

            echo "<th>".$LANG['financial'][3]."</th>";
            echo "<th colspan='2'>&nbsp;</th>";

            echo "</tr>";
         } else {
            echo "<br><div class='center'>";
            echo $LANG['cartridges'][7];
            echo "</div>";
         }
      }

      if (!$show_old) { // NEW
         $where= " AND `glpi_cartridges`.`date_out` IS NULL";
      } else { //OLD
         $where= " AND `glpi_cartridges`.`date_out` IS NOT NULL";
      }

      $stock_time=0;
      $use_time=0;
      $pages_printed=0;
      $nb_pages_printed=0;
      $ORDER="`glpi_cartridges`.`date_use` ASC, `glpi_cartridges`.`date_out` DESC,
              `glpi_cartridges`.`date_in`";
      if (!$show_old) {
         $ORDER=" `glpi_cartridges`.`date_out` ASC, `glpi_cartridges`.`date_use` ASC,
                  `glpi_cartridges`.`date_in`";
      }
      $query = "SELECT `glpi_cartridges`.*, `glpi_printers`.`id` as printID,
                       `glpi_printers`.`name` as printname, `glpi_printers`.`init_pages_counter`
                FROM `glpi_cartridges`
                LEFT JOIN `glpi_printers` ON (`glpi_cartridges`.`printers_id` = `glpi_printers`.`id`)
                WHERE (`glpi_cartridges`.`cartridgeitems_id` = '$tID') $where
                ORDER BY $ORDER";

      $pages=array();

      if ($result = $DB->query($query)) {
         $number=$DB->numrows($result);
         while ($data=$DB->fetch_array($result)) {
            $date_in=convDate($data["date_in"]);
            $date_use=convDate($data["date_use"]);
            $date_out=convDate($data["date_out"]);
            $printer=$data["printers_id"];
            $page=$data["pages"];

            echo "<tr class='tab_bg_1'><td class='center'>";
            echo $data["id"];
            echo "</td><td class='center'>";
            echo getCartridgeStatus($data["date_use"],$data["date_out"]);
            echo "</td><td class='center'>";
            echo $date_in;
            echo "</td><td class='center'>";
            echo $date_use;
            echo "</td><td class='center'>";
            if (!is_null($date_use)) {
               if ($data["printID"]>0) {
                  echo "<a href='".$CFG_GLPI["root_doc"]."/front/printer.form.php?id=".
                        $data["printID"]."'><strong>".$data["printname"];
                  if ($_SESSION['glpiis_ids_visible']) {
                     echo " (".$data["printID"].")";
                  }
                  echo "</strong></a>";
               } else {
                  echo "N/A";
               }
               $tmp_dbeg=explode("-",$data["date_in"]);
               $tmp_dend=explode("-",$data["date_use"]);
               $stock_time_tmp= mktime(0,0,0,$tmp_dend[1],$tmp_dend[2],$tmp_dend[0])
                  - mktime(0,0,0,$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);
               $stock_time+=$stock_time_tmp;
            }
            echo "</td><td class='center'>";
            echo $date_out;
            if ($show_old) {
               $tmp_dbeg=explode("-",$data["date_use"]);
               $tmp_dend=explode("-",$data["date_out"]);
               $use_time_tmp= mktime(0,0,0,$tmp_dend[1],$tmp_dend[2],$tmp_dend[0])
                  - mktime(0,0,0,$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);
               $use_time+=$use_time_tmp;
            }

            echo "</td>";
            if ($show_old) {
               // Get initial counter page
               if (!isset($pages[$printer])) {
                  $pages[$printer]=$data['init_pages_counter'];
               }
               echo "<td class='center'>";
               if ($pages[$printer]<$data['pages']) {
                  $pages_printed+=$data['pages']-$pages[$printer];
                  $nb_pages_printed++;
                  echo ($data['pages']-$pages[$printer])." ".$LANG['printers'][31];
                  $pages[$printer]=$data['pages'];
               }
               echo "</td>";
            }
            echo "<td class='center'>";
            showDisplayInfocomLink(CARTRIDGE_TYPE,$data["id"],1);
            echo "</td>";
            echo "<td class='center'>";
            if (!is_null($date_use) && $canedit) {
               echo "<a href='".$CFG_GLPI["root_doc"]."/front/cartridge.edit.php?restore=restore&amp;id=".
                  $data["id"]."&amp;tID=$tID'>".$LANG['consumables'][37]."</a>";
            } else {
                echo "&nbsp;";
            }
            echo "</td>";
            echo "<td class='center'>";
            if ($canedit) {
               echo "<a href='".$CFG_GLPI["root_doc"]."/front/cartridge.edit.php?delete=delete&amp;id=".
                  $data["id"]."&amp;tID=$tID'>".$LANG['buttons'][6]."</a>";
            } else {
                echo "&nbsp;";
            }
            echo "</td></tr>";
         }
         if ($show_old&&$number>0) {
            if ($nb_pages_printed==0) {
                $nb_pages_printed=1;
            }
            echo "<tr class='tab_bg_2'><td colspan='3'>&nbsp;</td>";
            echo "<td class='center'>".$LANG['cartridges'][40]."&nbsp;:<br>";
            echo round($stock_time/$number/60/60/24/30.5,1)." ".$LANG['financial'][57]."</td>";
            echo "<td>&nbsp;</td>";
            echo "<td class='center'>".$LANG['cartridges'][41]."&nbsp;:<br>";
            echo round($use_time/$number/60/60/24/30.5,1)." ".$LANG['financial'][57]."</td>";
            echo "<td class='center'>".$LANG['cartridges'][42]."&nbsp;:<br>";
            echo round($pages_printed/$nb_pages_printed)."</td>";
            echo "<td colspan='3'>&nbsp;</td></tr>";
         }
      }
      echo "</table></div>\n\n";
   }
}

/**
 * Show the printer types that are compatible with a cartridge type
 *
 * Show the printer types that are compatible with the cartridge type identified by $instID
 *
 *@param $instID : cartridge type identifier
 *
 *@return nothing (display)
 *
 **/
function showCompatiblePrinters($instID) {
   global $DB,$CFG_GLPI, $LANG;

   if (!haveRight("cartridge","r")) {
      return false;
   }

   if ($instID > 0) {
      $query = "SELECT `glpi_cartridges_printersmodels`.`id`,
                       `glpi_printersmodels`.`name` as `type`,
                       `glpi_printersmodels`.`id` as `pmid`
                FROM `glpi_cartridges_printersmodels`, `glpi_printersmodels`
                WHERE `glpi_cartridges_printersmodels`.`printersmodels_id` = `glpi_printersmodels`.`id`
                      AND `glpi_cartridges_printersmodels`.`cartridgeitems_id` = '$instID'
                ORDER BY `glpi_printersmodels`.`name`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i = 0;

      echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/cartridge.form.php\">";
      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='3'>".$LANG['cartridges'][32]."&nbsp;:</th></tr>";
      echo "<tr><th>".$LANG['common'][2]."</th><th>".$LANG['common'][22]."</th><th>&nbsp;</th></tr>";

      $used = array();
      while ($i < $number) {
         $ID=$DB->result($result, $i, "id");
         $type=$DB->result($result, $i, "type");
         $pmid=$DB->result($result, $i, "pmid");
         echo "<tr class='tab_bg_1'><td class='center'>$ID</td>";
         echo "<td class='center'>$type</td>";
         echo "<td class='tab_bg_2 center'>";
         echo "<a href='".$CFG_GLPI['root_doc'].
            "/front/cartridge.form.php?deletetype=deletetype&amp;id=$ID&amp;tID=$instID'>";
         echo "<strong>".$LANG['buttons'][6]."</strong></a></td></tr>";
         $used[]=$pmid;
         $i++;
      }
      if (haveRight("cartridge","w")) {
         echo "<tr class='tab_bg_1'><td>&nbsp;</td><td class='center'>";
         echo "<div class='software-instal'><input type='hidden' name='tID' value='$instID'>";
         dropdown("glpi_printersmodels","printersmodels_id",1,-1,$used);
         echo "</div></td><td class='tab_bg_2 center'>";
         echo "<input type='submit' name='addtype' value=\"".$LANG['buttons'][8]."\"
                class='submit'>";
         echo "</td></tr>";
      }
      echo "</table></div></form>"    ;
   }
}

/**
 * Show installed cartridges
 *
 * Show installed cartridge for the printer type $instID
 *
 *@param $instID integer: printer type identifier.
 *@param $old boolean : old cartridges or not ?
 *
 *@return nothing (display)
 *
 **/
function showCartridgeInstalled($instID,$old=0) {
   global $DB,$CFG_GLPI, $LANG;

   if (!haveRight("cartridge","r")) {
      return false;
   }
   $canedit=haveRight("cartridge","w");

   $query = "SELECT `glpi_cartridgeitems`.`id` as tID, `glpi_cartridgeitems`.`is_deleted`,
                    `glpi_cartridgeitems`.`ref` as ref, `glpi_cartridgeitems`.`name` as type,
                    `glpi_cartridges`.`id`, `glpi_cartridges`.`pages` as pages,
                    `glpi_cartridges`.`date_use` as date_use,
                    `glpi_cartridges`.`date_out` as date_out,
                    `glpi_cartridges`.`date_in` as date_in";
   if ($old==0) {
      $query.= " FROM `glpi_cartridges`, `glpi_cartridgeitems`
                 WHERE `glpi_cartridges`.`date_out` IS NULL
                       AND `glpi_cartridges`.`printers_id` = '$instID'
                       AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                 ORDER BY `glpi_cartridges`.`date_out` ASC, `glpi_cartridges`.`date_use` DESC,
                          `glpi_cartridges`.`date_in`";
   } else {
      $query.= " FROM `glpi_cartridges`, `glpi_cartridgeitems`
                 WHERE `glpi_cartridges`.`date_out` IS NOT NULL
                       AND `glpi_cartridges`.`printers_id` = '$instID'
                       AND `glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                 ORDER BY `glpi_cartridges`.`date_out` ASC, `glpi_cartridges`.`date_use` DESC,
                          `glpi_cartridges`.`date_in`";
   }

   $result = $DB->query($query);
   $number = $DB->numrows($result);
   $i = 0;
   $p=new Printer;
   $p->getFromDB($instID);
   $pages=$p->fields['init_pages_counter'];

   echo "<br><br><div class='center'><table class='tab_cadre_fixe'>";
   if ($old==0) {
      echo "<tr><th colspan='7'>".$LANG['cartridges'][33]."&nbsp;:</th></tr>";
   } else {
      echo "<tr><th colspan='8'>".$LANG['cartridges'][35]."&nbsp;:</th></tr>";
   }

   echo "<tr><th>".$LANG['common'][2]."</th><th>".$LANG['cartridges'][12]."</th>";
   echo "<th>".$LANG['consumables'][23]."</th><th>".$LANG['cartridges'][24]."</th>";
   echo "<th>".$LANG['consumables'][26]."</th><th>".$LANG['search'][9]."</th>";
   if ($old!=0) {
      echo "<th>".$LANG['cartridges'][39]."</th>";
   }

   echo "<th>&nbsp;</th></tr>";

   $stock_time=0;
   $use_time=0;
   $pages_printed=0;
   $nb_pages_printed=0;
   $ci=new CommonItem();
   while ($data=$DB->fetch_array($result)) {
      $date_in=convDate($data["date_in"]);
      $date_use=convDate($data["date_use"]);
      $date_out=convDate($data["date_out"]);
      echo "<tr class='tab_bg_1".($data["is_deleted"]?"_2":"")."'><td class='center'>";
      echo $data["id"];
      echo "</td><td class='center'><strong>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/cartridge.form.php?id=".$data["tID"]."\">";
      echo $data["type"]." - ".$data["ref"];
      echo "</a>";
      echo "</strong></td><td class='center'>";
      echo getCartridgeStatus($data["date_use"],$data["date_out"]);
      echo "</td><td class='center'>";
      echo $date_in;
      echo "</td><td class='center'>";
      echo $date_use;

      $tmp_dbeg=explode("-",$data["date_in"]);
      $tmp_dend=explode("-",$data["date_use"]);

      $stock_time_tmp= mktime(0,0,0,$tmp_dend[1],$tmp_dend[2],$tmp_dend[0])
         - mktime(0,0,0,$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);
      $stock_time+=$stock_time_tmp;

      echo "</td><td class='center'>";
      echo $date_out;

      if ($old!=0) {
         $tmp_dbeg=explode("-",$data["date_use"]);
         $tmp_dend=explode("-",$data["date_out"]);

         $use_time_tmp= mktime(0,0,0,$tmp_dend[1],$tmp_dend[2],$tmp_dend[0])
            - mktime(0,0,0,$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);
         $use_time+=$use_time_tmp;
      }

      echo "</td><td class='center'>";
      if ($old!=0) {
         if ($canedit) {
            echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/cartridge.edit.php\">";
            echo "<input type='hidden' name='cID' value='".$data['id']."'>";
            echo "<input type='text' name='pages' value=\"".$data['pages']."\" size='10'>";
            echo "<input type='image' name='update_pages' value='update_pages'
                   src='".$CFG_GLPI["root_doc"]."/pics/actualiser.png' class='calendrier'>";
            echo "</form>";
         } else {
            echo "<input type='text' name='pages' value=\"".$data['pages']."\" size='10'>";
         }

         if ($pages<$data['pages']) {
            $pages_printed+=$data['pages']-$pages;
            $nb_pages_printed++;
            echo ($data['pages']-$pages)." ".$LANG['printers'][31];
            $pages=$data['pages'];
         }
         echo "</td><td class='center'>";
      }
      if ($canedit) {
         if (is_null($date_out)) {
            echo "&nbsp;&nbsp;&nbsp;";
            echo "<a href='".$CFG_GLPI["root_doc"].
               "/front/cartridge.edit.php?uninstall=uninstall&amp;id=".$data["id"].
               "&amp;tID=".$data["tID"]."'>".$LANG['cartridges'][29]."</a>";
         } else {
            echo "&nbsp;&nbsp;&nbsp;";
            echo "<a href='".$CFG_GLPI["root_doc"].
               "/front/cartridge.edit.php?delete=delete&amp;id=".$data["id"].
               "&amp;tID=".$data["tID"]."'>".$LANG['buttons'][6]."</a>";
         }
         echo "</td></tr>";
      }
   }
   if ($old==0&&$canedit) {
      echo "<tr class='tab_bg_1'><td>&nbsp;</td><td class='center' colspan='5'>";
      echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/cartridge.edit.php\">";
      echo "<div class='software-instal'><input type='hidden' name='pID' value='$instID'>";
      if (dropdownCompatibleCartridges($instID)) {
         echo "&nbsp;<input type='submit' name='install' value=\"".$LANG['buttons'][4]."\"
                      class='submit'>";
      }
      echo "</div></form></td><td class='tab_bg_2 center'>&nbsp;";
      echo "</td></tr>";
   } else { // Print average
      if ($number>0) {
         if ($nb_pages_printed==0) {
            $nb_pages_printed=1;
         }
         echo "<tr class='tab_bg_2'><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
         echo "<td class='center'>".$LANG['cartridges'][40]."&nbsp;:<br>";
         echo round($stock_time/$number/60/60/24/30.5,1)." ".$LANG['financial'][57]."</td>";
         echo "<td class='center'>".$LANG['cartridges'][41].":<br>";
         echo round($use_time/$number/60/60/24/30.5,1)." ".$LANG['financial'][57]."</td>";
         echo "<td class='center'>".$LANG['cartridges'][42].":<br>";
         echo round($pages_printed/$nb_pages_printed)."</td>";
         echo "<td>&nbsp;</td></tr>";
      }
   }
   echo "</table></div>";
}

/**
 * Print the cartridge count HTML array for a defined cartridge type
 *
 * Print the cartridge count HTML array for the cartridge item $tID
 *
 *@param $tID integer: cartridge item identifier.
 *@param $alarm_threshold integer: threshold alarm value.
 *@param $nohtml integer: Return value without HTML tags.
 *
 *@return string to display
 *
 **/
function countCartridges($tID,$alarm_threshold,$nohtml=0) {
   global $DB,$CFG_GLPI, $LANG;

   // Get total
   $total = getCartridgesNumber($tID);
   $out="";
   if ($total!=0) {
      $unused=getUnusedCartridgesNumber($tID);
      $used=getUsedCartridgesNumber($tID);
      $old=getOldCartridgesNumber($tID);
      $highlight="";
      if ($unused<=$alarm_threshold) {
         $highlight="class='tab_bg_1_2'";
      }

      if (!$nohtml) {
         $out.= "<div $highlight>".$LANG['common'][33].":&nbsp;$total&nbsp;&nbsp;&nbsp;<strong>".
                  $LANG['cartridges'][13]."&nbsp;: $unused</strong>&nbsp;&nbsp;&nbsp;".
                  $LANG['cartridges'][14]."&nbsp;: $used&nbsp;&nbsp;&nbsp;".
                  $LANG['cartridges'][15]."&nbsp;: $old</div>";
      } else {
         $out.= $LANG['common'][33]."&nbsp;: $total   ".
                $LANG['cartridges'][13]."&nbsp;: $unused   ".
                $LANG['cartridges'][14]."&nbsp;: $used   ".$LANG['cartridges'][15]."&nbsp;: $old";
      }
   } else {
      if (!$nohtml) {
         $out.= "<div class='tab_bg_1_2'><i>".$LANG['cartridges'][9]."</i></div>";
      } else {
         $out.= $LANG['cartridges'][9];
      }
   }
   return $out;
}

/**
 * count how many cartbridge for a cartbridge type
 *
 * count how many cartbridge for the cartridge item $tID
 *
 *@param $tID integer: cartridge item identifier.
 *
 *@return integer : number of cartridge counted.
 *
 **/
function getCartridgesNumber($tID) {
   global $DB;

   $query = "SELECT id
             FROM `glpi_cartridges`
             WHERE (`cartridgeitems_id` = '$tID')";
   $result = $DB->query($query);
   return $DB->numrows($result);
}

/**
 * count how many cartridge used for a cartbridge type
 *
 * count how many cartridge used for the cartbridge item $tID
 *
 *@param $tID integer: cartridge item identifier.
 *
 *@return integer : number of cartridge used counted.
 *
 **/
function getUsedCartridgesNumber($tID) {
   global $DB;

   $query = "SELECT id
             FROM `glpi_cartridges`
             WHERE (`cartridgeitems_id` = '$tID'
                    AND `date_use` IS NOT NULL
                    AND `date_out` IS NULL)";
   $result = $DB->query($query);
   return $DB->numrows($result);
}

/**
 * count how many old cartbridge for a cartbridge type
 *
 * count how many old cartbridge for the cartbridge item $tID
 *
 *@param $tID integer: cartridge item identifier.
 *
 *@return integer : number of old cartridge counted.
 *
 **/
function getOldCartridgesNumber($tID) {
   global $DB;

   $query = "SELECT id
             FROM `glpi_cartridges`
             WHERE (`cartridgeitems_id` = '$tID'
                    AND `date_out` IS NOT NULL)";
   $result = $DB->query($query);
   return $DB->numrows($result);
}

/**
 * count how many cartbridge unused for a cartbridge type
 *
 * count how many cartbridge unused for the cartbridge item $tID
 *
 *@param $tID integer: cartridge item identifier.
 *
 *@return integer : number of cartridge unused counted.
 *
 **/
function getUnusedCartridgesNumber($tID) {
   global $DB;

   $query = "SELECT id
             FROM `glpi_cartridges`
             WHERE (`cartridgeitems_id` = '$tID'
                    AND `date_use` IS NULL)";
   $result = $DB->query($query);
   return $DB->numrows($result);
}

/**
 * Print a select with compatible cartridge
 *
 * Print a select that contains compatibles cartridge for a printer model $pID
 *
 *@param $pID integer: printer type identifier.
 *
 *@return nothing (display)
 *
 **/
function dropdownCompatibleCartridges($pID) {
   global $DB,$LANG;

   $p=new Printer;
   $p->getFromDB($pID);

   $query = "SELECT COUNT(*) AS cpt, `glpi_locations`.`completename` as location,
                    `glpi_cartridgeitems`.`ref` as ref, `glpi_cartridgeitems`.`name` as name,
                    `glpi_cartridgeitems`.`id` as tID
             FROM `glpi_cartridgeitems`
             INNER JOIN `glpi_cartridges_printersmodels`
                        ON (`glpi_cartridgeitems`.`id` =
                           `glpi_cartridges_printersmodels`.`cartridgeitems_id`)
             INNER JOIN `glpi_cartridges`
                        ON (`glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                            AND `glpi_cartridges`.`date_use` IS NULL)
             LEFT JOIN `glpi_locations`
                       ON (`glpi_locations`.`id` = `glpi_cartridgeitems`.`locations_id`)
             WHERE `glpi_cartridges_printersmodels`.`printersmodels_id` =
                     '".$p->fields["printersmodels_id"]."'
                   AND `glpi_cartridgeitems`.`entities_id` ='".$p->fields["entities_id"]."'
             GROUP BY tID
             ORDER BY `name`, `ref`";
   if ($result = $DB->query($query)) {
      if ($DB->numrows($result)) {
         echo "<select name='tID' size=1>";
         while ($data= $DB->fetch_assoc($result)) {
            echo "<option value='".$data["tID"]."'>".$data["name"]." - ".$data["ref"]."
                  (".$data["cpt"]." ".$LANG['cartridges'][13].") - ".$data["location"]."</option>";
         }
         echo "</select>";
         return true;
      }
   }
   return false;
}

/**
 * Get the dict value for the status of a cartridge
 *
*@param $date_use date : date of use
*@param $date_out date : date of delete
 *
 *@return string : dict value for the cartridge status.
 *
 **/
function getCartridgeStatus($date_use,$date_out) {
   global $LANG;

   if (is_null($date_use) || empty($date_use)) {
      return $LANG['cartridges'][20];
   }
   else if (is_null($date_out) || empty($date_out)) {
      return $LANG['cartridges'][21];
   } else {
      return $LANG['cartridges'][22];
   }
}

/**
 * Cron action on cartridges : alert if a stock is behind the threshold
 * @param $task for log, display informations if NULL?
 * @return 0 : nothing to do 1 : done with success
 *
 **/
function cron_cartridge($task=NULL) {
   global $DB,$CFG_GLPI,$LANG;

   if (!$CFG_GLPI["use_mailing"] || !$CFG_GLPI["cartridges_alert_repeat"]) {
      return false;
   }

   loadLanguage($CFG_GLPI["language"]);

   // Get cartridges type with alarm activated and last warning > X days depending on config
   $query="SELECT `glpi_cartridgeitems`.`id` AS cartID,
                  `glpi_cartridgeitems`.`entities_id` as entity,
                  `glpi_cartridgeitems`.`ref` as cartref, `glpi_cartridgeitems`.`name` AS cartname,
                  `glpi_cartridgeitems`.`alarm_threshold` AS threshold,
                  `glpi_alerts`.`id` AS alertID, `glpi_alerts`.`date`
           FROM `glpi_cartridgeitems`
           LEFT JOIN `glpi_alerts`
                     ON (`glpi_cartridgeitems`.`id` = `glpi_alerts`.`items_id`
                         AND `glpi_alerts`.`itemtype` = '".CARTRIDGEITEM_TYPE."')
           WHERE `glpi_cartridgeitems`.`is_deleted` = '0'
                 AND `glpi_cartridgeitems`.`alarm_threshold` >= '0'
                 AND (`glpi_alerts`.`date` IS NULL
                      OR (`glpi_alerts`.date+".$CFG_GLPI["cartridges_alert_repeat"].")
                          < CURRENT_TIMESTAMP())
          ORDER BY `cartname`;";

   $result=$DB->query($query);

   $message=array();
   $items=array();
   $alert=new Alert();

   if ($DB->numrows($result)>0) {
      while ($data=$DB->fetch_array($result)) {
         if (($unused=getUnusedCartridgesNumber($data["cartID"]))<=$data["threshold"]) {
            if (!isset($message[$data["entity"]])) {
               $message[$data["entity"]]="";
            }
            if (!isset($items[$data["entity"]])) {
               $items[$data["entity"]]=array();
            }

            // define message alert
            $message[$data["entity"]].=$LANG['mailing'][34]." ".$data["cartname"]." - ".
                                       $LANG['consumables'][2].": ".$data["cartref"]." - ".
                                       $LANG['software'][20].": ".$unused."<br>\n";
            $items[$data["entity"]][]=$data["cartID"];

            // if alert exists -> delete
            if (!empty($data["alertID"])) {
               $alert->delete(array("id"=>$data["alertID"]));
            }
         }
      }

      if (count($message)>0) {
         foreach ($message as $entity => $msg) {
            $mail=new MailingAlert("alertcartridge",$msg,$entity);

            if ($mail->send()) {
               if ($task) {
                  $task->log(getDropdownName("glpi_entities",$entity).":  $msg\n");
                  $task->addVolume(1);
               } else {
                  addMessageAfterRedirect(getDropdownName("glpi_entities",$entity)."&nbsp;:  $msg");
               }

               $input["type"]=ALERT_THRESHOLD;
               $input["itemtype"]=CARTRIDGEITEM_TYPE;

               //// add alerts
               foreach ($items[$entity] as $ID) {
                  $input["items_id"]=$ID;
                  $alert->add($input);
                  unset($alert->fields['id']);
               }
            } else {
               if ($task) {
                  $task->log(getDropdownName("glpi_entities",$entity).
                         "&nbsp;: Send cartdridge alert failed");
               } else {
                  addMessageAfterRedirect(getDropdownName("glpi_entities",$entity).
                                          "&nbsp;: Send cartridge alert failed",false,ERROR);
               }
            }
         }
         return 1;
      }
   }
   return 0;
}
?>
