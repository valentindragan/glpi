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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/** Get device table based on device type
*@param $dev_type device type
*@return table name string
*/
function getDeviceTable($dev_type) {

   switch ($dev_type) {
      case MOBOARD_DEVICE :
         return "glpi_devicemotherboards";
         break;

      case PROCESSOR_DEVICE :
         return "glpi_deviceprocessors";
         break;

      case RAM_DEVICE :
         return "glpi_devicememories";
         break;

      case HDD_DEVICE :
         return "glpi_deviceharddrives";
         break;

      case NETWORK_DEVICE :
         return "glpi_devicenetworkcards";
         break;

      case DRIVE_DEVICE :
         return "glpi_devicedrives";
         break;

      case CONTROL_DEVICE :
         return "glpi_devicecontrols";
         break;

      case GFX_DEVICE :
         return "glpi_devicegraphiccards";
         break;

      case SND_DEVICE :
         return "glpi_devicesoundcards";
         break;

      case PCI_DEVICE :
         return "glpi_devicepcis";
         break;

      case CASE_DEVICE :
         return "glpi_devicecases";
         break;

      case POWER_DEVICE :
         return "glpi_devicepowersupplies";
         break;
   }
}

/** Get device specifity label based on device type
*@param $dev_type device type
*@return specifity label string
*/
function getDeviceSpecifityLabel($dev_type) {
   global $LANG;

   switch ($dev_type) {
      case MOBOARD_DEVICE :
         return "";
         break;

      case PROCESSOR_DEVICE :
         return $LANG['device_ram'][1];
         break;

      case RAM_DEVICE :
         return  $LANG['device_ram'][2];
         break;

      case HDD_DEVICE :
         return $LANG['device_hdd'][4];
         break;

      case NETWORK_DEVICE :
         return $LANG['device_iface'][2];
         break;

      case DRIVE_DEVICE :
         return "";
         break;

      case CONTROL_DEVICE :
         return "";
         break;

      case GFX_DEVICE :
         return  $LANG['device_gfxcard'][0];
         break;

      case SND_DEVICE :
         return "";
         break;

      case PCI_DEVICE :
         return "";
         break;

      case CASE_DEVICE :
         return "";
         break;

      case POWER_DEVICE :
         return "";
         break;
   }
}

/**
 * Get device type name based on device type
 *
 * @param $device_num device type
 * @return if $device_num == -1 return array of names else return device name
 **/
function getDictDeviceLabel($device_num=-1) {
   global $LANG;

   $dp=array();
   $dp[MOBOARD_DEVICE]=$LANG['devices'][5];
   $dp[PROCESSOR_DEVICE]=$LANG['devices'][4];
   $dp[NETWORK_DEVICE]=$LANG['devices'][3];
   $dp[RAM_DEVICE]=$LANG['devices'][6];
   $dp[HDD_DEVICE]=$LANG['devices'][1];
   $dp[DRIVE_DEVICE]=$LANG['devices'][19];
   $dp[CONTROL_DEVICE]=$LANG['devices'][20];
   $dp[GFX_DEVICE]=$LANG['devices'][2];
   $dp[SND_DEVICE]=$LANG['devices'][7];
   $dp[PCI_DEVICE]=$LANG['devices'][21];
   $dp[CASE_DEVICE]=$LANG['devices'][22];
   $dp[POWER_DEVICE]=$LANG['devices'][23];
   if ($device_num==-1) {
      return $dp;
   } else {
      return $dp[$device_num];
   }
}

/**  Update an internal device specificity
* @param $newValue new specifity value
* @param $compDevID computer device ID
* @param $strict update based on ID
* @param $checkcoherence check coherence of new value before updating : do not update if it is not coherent
*/
function update_device_specif($newValue,$compDevID,$strict=false,$checkcoherence=false) {
   global $DB;

   // Check old value for history
   $query ="SELECT *
            FROM `glpi_computers_devices`
            WHERE `id` = '".$compDevID."'";

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result)) {
         $data = addslashes_deep($DB->fetch_array($result));
         if ($checkcoherence) {
            switch ($data["devicetype"]) {
               case PROCESSOR_DEVICE :
                  //Prevent division by O error if newValue is null or doesn't contains any value
                  if ($newValue == null || $newValue=='') {
                     return false;
                  }
                  //Calculate pourcent change of frequency
                  $pourcent =  ( $newValue / ($data["specificity"] / 100) ) - 100;
                  //If new processor speed value is superior to the old one,
                  //and if the change is at least 5% change
                  if ($data["specificity"] < $newValue && $pourcent > 4) {
                     $condition = true;
                  } else {
                     $condition = false;
                  }
                  break;

               case GFX_DEVICE :
                  //If memory has changed and his new value is not 0
                  if ($data["specificity"] != $newValue && $newValue > 0) {
                     $condition = true;
                  } else {
                     $condition = false;
                  }
                  break;

               default :
                  if ($data["specificity"] != $newValue) {
                     $condition = true;
                  } else {
                     $condition = false;
                  }
                  break;
            }
         } else {
            if ($data["specificity"] != $newValue) {
               $condition = true;
            } else {
               $condition = false;
            }
         }
         // Is it a real change ?
         if( $condition) {
            // Update specificity
            $WHERE=" WHERE `devices_id` = '".$data["devices_id"]."'
                           AND `computers_id` = '".$data["computers_id"]."'
                           AND `devicetype` = '".$data["devicetype"]."'
                           AND `specificity` = '".$data["specificity"]."'";
            if ($strict) {
                $WHERE=" WHERE `id` = '$compDevID'";
            }

            $query2 = "UPDATE
                       `glpi_computers_devices`
                       SET `specificity` = '".$newValue."' $WHERE";
            if ($DB->query($query2)) {
               $changes[0]='0';
               $changes[1]=addslashes($data["specificity"]);
               $changes[2]=$newValue;
               // history log
               historyLog ($data["computers_id"],'Computer',$changes,$data["devicetype"],
                           HISTORY_UPDATE_DEVICE);
               return true;
            } else {
               return false;
            }
         }
      }
   }
}

/**  Update an internal device quantity
* @param $newNumber new quantity value
* @param $compDevID computer device ID
*/
function update_device_quantity($newNumber,$compDevID) {
   global $DB;

   // Check old value for history
   $query ="SELECT *
            FROM `glpi_computers_devices`
            WHERE `id` = '".$compDevID."'";
   if ($result = $DB->query($query)) {
      $data = addslashes_deep($DB->fetch_array($result));

      $query2 = "SELECT `id`
                 FROM `glpi_computers_devices`
                 WHERE `devices_id` = '".$data["devices_id"]."'
                       AND `computers_id` = '".$data["computers_id"]."'
                       AND `devicetype` = '".$data["devicetype"]."'
                       AND `specificity` = '".$data["specificity"]."'";
      if ($result2 = $DB->query($query2)) {
         // Delete devices
         $number=$DB->numrows($result2);
         if ($number>$newNumber) {
            for ($i=$newNumber;$i<$number;$i++) {
               $data2 = $DB->fetch_array($result2);
               unLink_ItemType_computer($data2["id"],1);
            }
         // Add devices
         } else if ($number<$newNumber) {
            for ($i=$number;$i<$newNumber;$i++) {
               compdevice_add($data["computers_id"],$data["devicetype"],$data["devices_id"],
                              $data["specificity"],1);
            }
         }
      }
   }
}

/**
 * Unlink a device, linked to a computer.
 *
 * Unlink a device and a computer witch link ID is $compDevID (on table glpi_computers_devices)
 *
 * @param $compDevID ID of the computer-device link (table glpi_computers_devices)
 * @param $dohistory log history updates ?
 * @returns boolean
 **/
function unLink_ItemType_computer($compDevID,$dohistory=1) {
   global $DB;

   // get old value  and id for history
   $query ="SELECT *
            FROM `glpi_computers_devices`
            WHERE `id` = '".$compDevID."'";
   if ($result = $DB->query($query)) {
      $data = $DB->fetch_array($result);
   }

   $query2 = "DELETE
              FROM `glpi_computers_devices`
              WHERE `id` = '".$compDevID."'";
   if ($DB->query($query2)) {
      if ($dohistory) {
         $device = new Device($data["devicetype"]);
         if ($device->getFromDB($data["devices_id"])) {
            $changes[0]='0';
            $changes[1]=addslashes($device->fields["designation"]);
            $changes[2]="";
            // history log
            historyLog ($data["computers_id"],'Computer',$changes,$data["devicetype"],
                        HISTORY_DELETE_DEVICE);
         }
      }
      return true;
   } else {
      return false;
   }
}

/**
 * Link the device to the computer
 *
 * @param $computers_id Computer ID
 * @param $devicetype device type
 * @param $dID device ID
 * @param $specificity specificity value
 * @param $dohistory do history log
 * @returns new computer device ID
 **/
function compdevice_add($computers_id,$devicetype,$dID,$specificity='',$dohistory=1) {

   $device = new Device($devicetype);
   $device->getFromDB($dID);
   if (empty($specificity)) {
      $specificity=$device->fields['specif_default'];
   }
   $newID=$device->computer_link($computers_id,$devicetype,$specificity);

   if ($dohistory) {
      $changes[0]='0';
      $changes[1]="";
      $changes[2]=addslashes($device->fields["designation"]);
      // history log
      historyLog ($computers_id,'Computer',$changes,$devicetype,HISTORY_ADD_DEVICE);
   }
   return $newID;
}



?>
