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
		require_once WB_PATH.'/modules/'.basename(dirname(__FILE__)).'//class.droplets.php';
	}
}

require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/class.syncdata.php');

global $admin;

$tables = array('dbSyncDataArchives','dbSyncDataCfg','dbSyncDataFiles','dbSyncDataJobs','dbSyncDataProtocol');
$error = '';

foreach ($tables as $table) {
	$delete = null;
	$delete = new $table();
	if ($delete->sqlTableExists()) {
		if (!$delete->sqlDeleteTable()) {
			$error .= sprintf('<p>[UNINSTALL] %s</p>', $delete->getError());
		}
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

// Prompt Errors
if (!empty($error)) {
	$admin->print_error($error);
}

?>