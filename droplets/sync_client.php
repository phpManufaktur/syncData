//:client interface for syncData
//:Please visit http://phpManufaktur.de for informations about kitForm!
/**
 * syncData
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 */
if (file_exists(WB_PATH.'/modules/sync_data/class.synchronize.php')) {
	require_once(WB_PATH.'/modules/sync_data/class.synchronize.php');
	$client = new syncClient();
	$params = $client->getParams();
	$params[syncClient::param_preset] = (isset($preset)) ? (int) $preset : 1;
	$params[syncClient::param_css] = (isset($css) && (strtolower($css) == 'false')) ? false : true;
	if (!$client->setParams($params)) return $client->getError();
	return $client->action();
}
else {
	return "syncData is not installed!";
}