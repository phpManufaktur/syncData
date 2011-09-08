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
 
// try to include LEPTON class.secure.php to protect this file and the whole CMS!
if (defined('WB_PATH')) {	
	if (defined('LEPTON_VERSION')) include(WB_PATH.'/framework/class.secure.php');
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'].'/framework/class.secure.php')) {
	include($_SERVER['DOCUMENT_ROOT'].'/framework/class.secure.php'); 
} else {
	$subs = explode('/', dirname($_SERVER['SCRIPT_NAME']));	$dir = $_SERVER['DOCUMENT_ROOT'];
	$inc = false;
	foreach ($subs as $sub) {
		if (empty($sub)) continue; $dir .= '/'.$sub;
		if (file_exists($dir.'/framework/class.secure.php')) { 
			include($dir.'/framework/class.secure.php'); $inc = true;	break; 
		} 
	}
	if (!$inc) trigger_error(sprintf("[ <b>%s</b> ] Can't include LEPTON class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
}
// end include LEPTON class.secure.php

// include language file for syncData
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

global $admin;

$tables = array('dbSyncDataArchives','dbSyncDataCfg','dbSyncDataFiles','dbSyncDataJobs','dbSyncDataProtocol');
$error = '';

foreach ($tables as $table) {
	$create = null;
	$create = new $table();
	if (!$create->sqlTableExists()) {
		if (!$create->sqlCreateTable()) {
			$error .= sprintf('<p>[INSTALLATION %s] %s</p>', $table, $create->getError());
		}
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