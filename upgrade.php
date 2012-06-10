<?php

/**
 * syncData
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 * 
 * FOR VERSION- AND RELEASE NOTES PLEASE LOOK AT INFO.TXT!
 */
 
// include LEPTON class.secure.php to protect this file and the whole CMS!
$class_secure = '../../framework/class.secure.php';
if (file_exists($class_secure)) {
	include($class_secure);
}
else {
	trigger_error(sprintf("[ <b>%s</b> ] Can't include LEPTON class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
}
 
// include language file for flexTable
if(!file_exists(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php')) {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/DE.php'); // Vorgabe: DE verwenden 
}
else { 
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php');
}

if (!class_exists('checkDroplets')) {
	// try to load required class.droplets.php
	if (file_exists(WB_PATH.'/modules/kit_tools/class.droplets.php')) {
		require_once WB_PATH.'/modules/kit_tools/class.droplets.php';
	}
	else {
		// load embedded class.droplets.php
		require_once WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.droplets.php';
	}
}

require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/class.syncdata.php');
require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/class.interface.php');

global $admin;
global $database;

$error = '';

/**
 * 0.10 --> 0.11
 */
// delete unused mod_sync_data_cronjob_data
$SQL = sprintf("DROP TABLE IF EXISTS %smod_sync_data_cronjob_data", TABLE_PREFIX);
$database->query($SQL);
if ($database->is_error()) {
	$error .= sprintf('<p>[DROP TABLE mod_sync_data_cronjob_data] %s</p>', $database->get_error());
}
// delete directory /htt
if (file_exists(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/htt')) {
	$interface = new syncDataInterface();
	if (!$interface->clearDirectory(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/htt', false)) {
		$error .= sprintf('<p>[DELETE DIRECTORY %s] %s</p>', '/modules/'.basename(dirname(__FILE__)).'/htt', $interface->getError());
	}
}

// remove Droplets
$dbDroplets = new dbDroplets();
$droplets = array('sync_client');
foreach ($droplets as $droplet) {
	$where = array(dbDroplets::field_name => $droplet);
	if (!$dbDroplets->sqlDeleteRecord($where)) {
		$message = sprintf('[UPGRADE] Error uninstalling Droplet: %s', $dbDroplets->getError());
	}	
}

// Install Droplets
$droplets = new checkDroplets();
$droplets->droplet_path = WB_PATH.'/modules/sync_data/droplets/';

if ($droplets->insertDropletsIntoTable()) {
  $message = sprintf(sync_msg_install_droplets_success, 'syncData');
}
else {
  $message = sprintf(sync_msg_install_droplets_failed, 'syncData', $droplets->getError());
}
if ($message != "") {
  echo '<script language="javascript">alert ("'.$message.'");</script>';
}


// Prompt Errors
if (!empty($error)) {
	$admin->print_error($error);
}

?>