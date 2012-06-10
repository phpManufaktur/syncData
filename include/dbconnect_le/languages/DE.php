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

define ( 'dbc_error_database_not_connected', 'Die Datenbank ist nicht verbunden!' );
define ( 'dbc_error_fieldDefinitionsNotChecked', 'Bitte rufen Sie zunaechst die Funktion<strong>checkFieldDefinitions()</strong> auf, bevor Sie Datenbankabfragen durchfuehren!' );
define ( 'dbc_error_emptyTableName', 'Der Tabellenname ist leer, bitte definieren Sie zunaechst einen Namen fuer die Tabelle!' );
define ( 'dbc_error_noFieldDefinitions', 'Die Felddefinitionen fehlen, legen Sie die zunaechst die Felder fuer die Datenbank fest!' );
define ( 'dbc_error_noPrimaryKey', 'Der Primaere Schluessel ist nicht definiert!' );
define ( 'dbc_error_execQuery', 'Fehler bei der SQL Abfrage: <b>%s</b>' );
define ( 'dbc_error_feature_not_supported', 'Diese Funktion wird von dbConnectLE nicht unterstuetzt!' );
define ( 'dbc_error_recordEmpty', 'Der uebergebene Datensatz ist leer, es ist nichts zu tun...' );
define ( 'dbc_error_csv_file_no_handle', 'Fuer die CSV Datei <b>%s</b> konnte kein Handle erzeugt werden.' );
define ( 'dbc_error_csv_no_keys', 'In der CSV Datei <b>%s</b> wurden keine Spaltenueberschriften gefunden!<br />Fuegen Sie Spaltenueberschriften in die CSV ein oder setzen Sie den Schalter <b>$has_header=false</b>.' );
define ( 'dbc_error_csv_file_put', 'Fehler beim Schreiben der CSV Datei <b>%s</b>.' );

?>