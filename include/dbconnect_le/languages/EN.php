<?php

/**
 * dbConnect_LE
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2008 - 2011
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

// Be aware to save this file in UTF-8 (no BOM) to keep special chars!

define ( 'dbc_error_database_not_connected', 'Database is not connected!' );
define ( 'dbc_error_fieldDefinitionsNotChecked', 'Please call function <strong>checkFieldDefinitions()</strong> before executing SQL Queries.' );
define ( 'dbc_error_emptyTableName', 'Empty table name, please define table name at first.' );
define ( 'dbc_error_noFieldDefinitions', 'No field definitions, please define fields for the database.' );
define ( 'dbc_error_noPrimaryKey', 'Undefined Primary Key, please define a primary key for the table.' );
define ( 'dbc_error_execQuery', 'Error while executing SQL Query: <b>%s</b>' );
define ( 'dbc_error_feature_not_supported', 'This function is not supported by dbConnectLE' );
define ( 'dbc_error_recordEmpty', 'The delivered record is empty (no data to process).' );
define ( 'dbc_error_csv_file_no_handle', 'Can\'t create handle for the CSV file <b>%s</b>!' );
define ( 'dbc_error_csv_no_keys', 'Missing column headers for the CSV file <b>%s</b>! Insert headers or set switch <b>$has_header=false</b>.' );
define ( 'dbc_error_csv_file_put', 'Error writing CSV the file <b>%s</b>.' );

?>