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

$module_directory     = 'sync_data';
$module_name          = 'syncData';
$module_function      = 'tool';
$module_version       = '0.50';
$module_status        = 'Beta';
$module_platform      = '2.8'; 
$module_author        = 'Ralf Hertsch, Berlin (Germany)';
$module_license       = 'GNU General Public License';
$module_description   = 'Data backup, restore and synchronize for WebsiteBaker and LEPTON CMS'; 
$module_home          = 'http://phpmanufaktur.de/sync_data';
$module_guid          = '44E09E74-6431-4232-9979-7C9BF615C921';

?>