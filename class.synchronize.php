<?php
/**
 * syncData
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 */ 

// include LEPTON class.secure.php to protect this file and the whole CMS!
$class_secure = '../../framework/class.secure.php';
if (file_exists($class_secure)) {
	include($class_secure);
}
else {
	trigger_error(sprintf("[ <b>%s</b> ] Can't include LEPTON class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
}

// include language file for syncData
if(!file_exists(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php')) {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/EN.php'); 
	if (!defined('SYNC_DATA_LANGUAGE')) define('SYNC_DATA_LANGUAGE', 'EN');
}
else {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php'); 
	if (!defined('SYNC_DATA_LANGUAGE')) define('SYNC_DATA_LANGUAGE', LANGUAGE);
}

if (!class_exists('Dwoo')) {
	// try to load regular Dwoo
	if (file_exists(WB_PATH.'/modules/dwoo/include.php')) {
		require_once WB_PATH.'/modules/dwoo/include.php';
	}
	else {
		// load Dwoo from include directory
		require_once WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/include/dwoo/dwooAutoload.php';
	}
}

$cache_path = WB_PATH.'/temp/cache';
if (!file_exists($cache_path)) mkdir($cache_path, 0777, true);
$compiled_path = WB_PATH.'/temp/compiled';
if (!file_exists($compiled_path)) mkdir($compiled_path, 0777, true);

global $parser;
if (!is_object($parser)) $parser = new Dwoo($compiled_path, $cache_path);

if (!class_exists('dbconnectle')) {
	// try to load regular dbConnect_LE
	if (file_exists(WB_PATH.'/modules/dbconnect_le/include.php')) {
		require_once WB_PATH.'/modules/dbconnect_le/include.php';
	}
	else {
		// load dbConnect_LE from include directory
		require_once WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/include/dbconnect_le/include.php';
	}
}

if (!class_exists('kitToolsLibrary')) {
	// try to load required kitTools
	if (file_exists(WB_PATH.'/modules/kit_tools/class.tools.php')) {
		require_once WB_PATH.'/modules/kit_tools/class.tools.php';
	}
	else {
		// load embedded kitTools library
		require_once WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.tools.php';
	}
}
global $kitTools;
if (!is_object($kitTools)) $kitTools = new kitToolsLibrary();

require_once WB_PATH.'/modules/pclzip/pclzip.lib.php';

require_once WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.syncdata.php';

global $dbSyncDataCfg;
if (!is_object($dbSyncDataCfg)) $dbSyncDataCfg = new dbSyncDataCfg();
global $dbSyncDataJob;
if (!is_object($dbSyncDataJob)) $dbSyncDataJob = new dbSyncDataJobs();
global $dbSyncDataArchive;
if (!is_object($dbSyncDataArchive)) $dbSyncDataArchive = new dbSyncDataArchives();

require_once WB_PATH.'/framework/functions.php';
require_once WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.interface.php';


class syncServer {
	
	const request_action						= 'act';
	const request_archive_id				= 'id';
	const request_archive_number		= 'no';
	
	const action_default						= 'def';
	const action_connect						= 'con';
	const action_info								= 'inf';
	
	const result_status							= 'status';
	const result_message						=	'message';
	const result_archive_id					= 'archive_id';
	const result_archive_number			= 'archive_number';
	const result_archive_file				= 'archive_file';
	const result_archive_md5				= 'archive_md5';
	const result_archive_size				= 'archive_size';
	const result_archive_timestamp	= 'archive_timestamp';
	
	const status_ok						= 1;
	const status_error				= 0;
	
	private $backup_path			= '';
	
	public function __construct() {
		$this->backup_path = WB_PATH.MEDIA_DIRECTORY.'/sync_data/backup/';
		
	} // __construct()
	
	/**
   * Return Version of Module
   *
   * @return FLOAT
   */
  public function getVersion() {
    // read info.php into array
    $info_text = file(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/info.php');
    if ($info_text == false) {
      return -1; 
    }
    // walk through array
    foreach ($info_text as $item) {
      if (strpos($item, '$module_version') !== false) {
        // split string $module_version
        $value = explode('=', $item);
        // return floatval
        return floatval(preg_replace('([\'";,\(\)[:space:][:alpha:]])', '', $value[1]));
      } 
    }
    return -1;
  } // getVersion()
	
  /**
   * Verhindert XSS Cross Site Scripting
   * 
   * @param REFERENCE $_REQUEST Array
   * @return $request
   */
	public function xssPrevent(&$request) { 
  	if (is_string($request)) {
	    $request = html_entity_decode($request);
	    $request = strip_tags($request);
	    $request = trim($request);
	    $request = stripslashes($request);
  	}
	  return $request;
  } // xssPrevent()
	
  
  public function action() {
  	$html_allowed = array();
  	foreach ($_REQUEST as $key => $value) {
  		if (!in_array($key, $html_allowed)) {
  			$_REQUEST[$key] = $this->xssPrevent($value);	  			
  		} 
  	}
    $action = isset($_REQUEST[self::request_action]) ? $_REQUEST[self::request_action] : self::action_default;
        
  	switch ($action):
  	case self::action_info:
  		$result = $this->actionInfo();
  		break;
  	case self::action_connect:
  		$result = $this->actionConnect();
  		break;
  	default:
  		$result = $this->actionForbidden();
  		break;
  	endswitch;
  	// return serialized result array
		echo serialize($result);
  } // action()

  public function actionForbidden() {
  	$result = array(
  		self::result_status		=> self::status_error,
  		self::result_message	=> sync_error_sync_action_forbidden
  	);
  	return $result;
  } // actionForbidden()
  
  private function actionConnect() {
  	global $dbSyncDataCfg;
  	global $dbSyncDataJob;
  	global $dbSyncDataArchive;
  	
  	$server_active = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgServerActive);
  	
  	if ($server_active == 0) {
  		// the server is not active
  		$result = array(
  			self::result_status		=> self::status_error,
  			self::result_message	=> sync_error_sync_server_inactive
  		);
  		return $result;
  	}
  	
  	$archive_id = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgServerArchiveID);
  	
  	if (empty($archive_id)) {
  		// server is active but there is no archive ID defined!
  		$result = array(
  			self::result_status 	=> self::status_error,
  			self::result_message	=> sync_error_sync_archive_id_missing
  		);
  		return $result;
  	}
  	
  	// check if an archive file exists
  	$SQL = sprintf( "SELECT * FROM %s,%s WHERE %s=%s AND %s=%s AND %s='%s' AND %s='%s' ORDER BY %s DESC LIMIT 1",
  									$dbSyncDataJob->getTableName(),
  									$dbSyncDataArchive->getTableName(),
  									dbSyncDataJobs::field_archive_id,
  									dbSyncDataArchives::field_archive_id,
  									dbSyncDataJobs::field_archive_number,
  									dbSyncDataArchives::field_archive_number,
  									dbSyncDataJobs::field_archive_id,
  									$archive_id,
  									dbSyncDataJobs::field_status,
  									dbSyncDataJobs::status_finished,
  									dbSyncDataJobs::field_archive_number);
  	if (!$dbSyncDataJob->sqlExec($SQL, $job)) {
  		// error requesting data
  		$result = array(
  			self::result_status		=> self::status_error,
  			self::result_message	=> sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError())
  		);
  		return $result;
  	}
  	if (count($job) < 1) {
  		// no job for this archive ID available
  		$result = array(
  			self::result_status		=> self::status_error,
  			self::result_message	=> sprintf(sync_error_sync_archive_id_invalid, $archive_id)
  		);
  		return $result;
  	}
  	
  	// check job and archive
  	$job = $job[0];

  	$archive_file = page_filename($job[dbSyncDataArchives::field_archive_name].'.zip');
  	
  	if (!file_exists($this->backup_path.$archive_file)) {
  		// backup archive ZIP file does not exists
  		$result = array(
  			self::result_status		=> self::status_error,
  			self::result_message	=> sprintf(sync_error_sync_archive_file_missing, $archive_file)
  		);
  		return $result;
  	}
  	
  	if (false === ($md5 = md5_file($this->backup_path.$archive_file))) {
  		// error getting md5 checksum
  		$result = array(
  			self::result_status		=> self::status_error,
  			self::result_message	=> sprintf(sync_error_sync_archive_file_get_md5, $archive_file)
  		);
  		return $result;
  	}
  	
  	if (false ===($size = filesize($this->backup_path.$archive_file))) {
  		// error getting filesize
  		$result = array(
  			self::result_status		=> self::status_error,
  			self::result_message	=> sprintf(sync_error_sync_archive_filesize, $archive_file)
  		);
  	}
  	$result = array(
  		self::result_status						=> self::status_ok,
  		self::result_message					=> '',
  		self::result_archive_id				=> $archive_id,
  		self::result_archive_md5			=> $md5,
  		self::result_archive_file			=> $archive_file,
  		self::result_archive_number 	=> $job[dbSyncDataJobs::field_archive_number],
  		self::result_archive_size			=> $size,
  		self::result_archive_timestamp=> $job[dbSyncDataJobs::field_timestamp]
  	);
  	return $result;
  } // actionConnect()
  
  /**
   * Return the informations for the requested Archive by Archive ID
   * and Archive Number
   * 
   * @return ARRAY $result
   */
  public function actionInfo() {
  	global $dbSyncDataArchive;
  	global $dbSyncDataJob;
  	
  	if (!isset($_REQUEST[self::request_archive_id]) || !isset($_REQUEST[self::request_archive_number])) {
  		$result = array(
  			self::result_status					=> self::status_error,
  			self::result_message				=> sync_error_sync_missing_params
  		);
  		return $result;
  	}
  	
  	$SQL = sprintf( "SELECT * FROM %s,%s WHERE %s=%s AND %s=%s AND %s='%s' AND %s='%s' AND %s='%s'",
  									$dbSyncDataArchive->getTableName(),
  									$dbSyncDataJob->getTableName(),
  									dbSyncDataArchives::field_archive_id,
  									dbSyncDataJobs::field_archive_id,
  									dbSyncDataArchives::field_archive_number,
  									dbSyncDataJobs::field_archive_number,
  									dbSyncDataJobs::field_archive_id,
  									$_REQUEST[self::request_archive_id],
  									dbSyncDataJobs::field_archive_number,
  									$_REQUEST[self::request_archive_number],
  									dbSyncDataJobs::field_status,
  									dbSyncDataJobs::status_finished);
  	$job = array();
  	if (!$dbSyncDataJob->sqlExec($SQL, $job)) {
  		$result = array(
  			self::result_status		=> self::status_error,
  			self::result_message	=> sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()) 
  		);
  		return $result;
  	}
  	
  	if (count($job) < 1) {
  		// requested archive does not exists
  		$result = array(
  			self::result_status		=> self::status_error,
  			self::result_message	=> sprintf(sync_error_archive_id_invalid, $_REQUEST[self::request_archive_id])
  		);
  		return $result;
  	}
  	$job = $job[0];
  	
  	$archive_file = page_filename($job[dbSyncDataArchives::field_archive_name].'.zip');
  	
  	if (!file_exists($this->backup_path.$archive_file)) {
  		// backup archive ZIP file does not exists
  		$result = array(
  			self::result_status		=> self::status_error,
  			self::result_message	=> sprintf(sync_error_sync_archive_file_missing, $archive_file)
  		);
  		return $result;
  	}
  	
  	if (false === ($md5 = md5_file($this->backup_path.$archive_file))) {
  		// error getting md5 checksum
  		$result = array(
  			self::result_status		=> self::status_error,
  			self::result_message	=> sprintf(sync_error_sync_archive_file_get_md5, $archive_file)
  		);
  		return $result;
  	}
  	
  	if (false ===($size = filesize($this->backup_path.$archive_file))) {
  		// error getting filesize
  		$result = array(
  			self::result_status		=> self::status_error,
  			self::result_message	=> sprintf(sync_error_sync_archive_filesize, $archive_file)
  		);
  	}
  	$result = array(
  		self::result_status						=> self::status_ok,
  		self::result_message					=> '',
  		self::result_archive_id				=> $job[dbSyncDataJobs::field_archive_id],
  		self::result_archive_md5			=> $md5,
  		self::result_archive_file			=> $archive_file,
  		self::result_archive_number 	=> $job[dbSyncDataJobs::field_archive_number],
  		self::result_archive_size			=> $size,
  		self::result_archive_timestamp=> $job[dbSyncDataJobs::field_timestamp]
  	);
  	return $result;
  } // actionInfo()
  
} // class syncServer

/**
 * The class for the syncData CLIENT - called by the droplet sync_client
 * 
 * @author Ralf Hertsch
 *
 */
class syncClient {
	
	const request_action								= 'act';
	const request_job_id								= 'job';

	const action_default								= 'def';
	const action_check_for_updates			= 'cup';
	const action_update_download				= 'udl';
	const action_update_start						= 'ust';
	const action_update_continue				= 'upc';
	
	private $page_link 									= '';
	private $template_path							= '';
	private $error											= '';
	private $message										= '';
	private $temp_path									= '';
	private $image_url									= '';
	
	const param_preset									= 'preset';
	//const param_server									= 'server';
	const param_css											= 'css';
	
	private $params = array(
		self::param_preset										=> 1, 
		//self::param_server										=> '',
		self::param_css												=> true,
	);
	
	private $server_url									= '';
	
	const session_server_request				= 'sync_server_request';
	const session_server_url						= 'sync_server_url';
	const session_further_update				= 'sync_server_further_update';
	
	public function __construct() {
		global $kitTools;
		global $dbSyncDataCfg;
		
		$url = '';
		$_SESSION['FRONTEND'] = true;	
		$kitTools->getPageLinkByPageID(PAGE_ID, $url);
		$this->page_link = $url; 
		$this->template_path = WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/templates/'.$this->params[self::param_preset].'/'.SYNC_DATA_LANGUAGE.'/' ;
		date_default_timezone_set(sync_cfg_time_zone);
		$this->temp_path = WB_PATH.'/temp/';
		$this->image_url = WB_URL.'/modules/'.basename(dirname(__FILE__)).'/images/';	
		
		$this->memory_limit = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgMemoryLimit);
		ini_set("memory_limit",$this->memory_limit);
		$this->max_execution_time = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgMaxExecutionTime);
		$this->limit_execution_time = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgLimitExecutionTime);
		set_time_limit($this->max_execution_time);
		// setting server URL
		$server_url = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgServerURL);
		$this->server_url = (empty($server_url)) ? WB_URL : $server_url;
	} // __construct()
	
	/**
    * Set $this->error to $error
    * 
    * @param STR $error
    */
  public function setError($error) {
  	$this->error = $error;
  } // setError()

  /**
    * Get Error from $this->error;
    * 
    * @return STR $this->error
    */
  public function getError() {
    return $this->error;
  } // getError()

  /**
    * Check if $this->error is empty
    * 
    * @return BOOL
    */
  public function isError() {
    return (bool) !empty($this->error);
  } // isError

  /**
   * Reset Error to empty String
   */
  public function clearError() { 
  	$this->error = ''; 
  }

  /** Set $this->message to $message
    * 
    * @param STR $message
    */
  public function setMessage($message) {
    $this->message = $message;
  } // setMessage()

  /**
    * Get Message from $this->message;
    * 
    * @return STR $this->message
    */
  public function getMessage() {
    return $this->message;
  } // getMessage()

  /**
    * Check if $this->message is empty
    * 
    * @return BOOL
    */
  public function isMessage() {
    return (bool) !empty($this->message);
  } // isMessage
  
  /**
   * Return Version of Module
   *
   * @return FLOAT
   */
  public function getVersion() {
    // read info.php into array
    $info_text = file(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/info.php');
    if ($info_text == false) {
      return -1; 
    }
    // walk through array
    foreach ($info_text as $item) {
      if (strpos($item, '$module_version') !== false) {
        // split string $module_version
        $value = explode('=', $item);
        // return floatval
        return floatval(preg_replace('([\'";,\(\)[:space:][:alpha:]])', '', $value[1]));
      } 
    }
    return -1;
  } // getVersion()
  
  /**
   * Get the desired $template within the template path, fills in the
   * $template_data and return the template output
   * 
   * @param STR $template
   * @param ARRAY $template_data
   * @return STR|BOOL template or FALSE on error
   */
  public function getTemplate($template, $template_data) {
  	global $parser;
    $result = 'TEMPLATE ERROR!';
  	try {
  		$result = $parser->get($this->template_path.$template, $template_data); 
  	} catch (Exception $e) {
  		$this->setError(sprintf(sync_error_template_error, $template, $e->getMessage()));
  		return false;
  	}
  	return $result;
  } // getTemplate()
  
  /**
   * Get the params
   * 
   * @return ARRAY self::params
   */
  public function getParams() {
		return $this->params;
	} // getParams()
	
	/**
	 * Set parameters
	 * 
	 * @param ARRAY $params
	 * @return BOOL
	 */
	public function setParams($params = array()) {
		$this->params = $params;
		$this->template_path = WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/templates/'.$this->params[self::param_preset].'/'.SYNC_DATA_LANGUAGE.'/';
		if (!file_exists($this->template_path)) {
			$this->setError(sprintf(sync_error_preset_not_exists, '/modules/'.basename(dirname(__FILE__)).'/templates/'.$this->params[self::param_preset].'/'.SYNC_DATA_LANGUAGE.'/'));
			return false;
		}
		return true;
	} // setParams()
	
  
  /**
   * Verhindert XSS Cross Site Scripting
   * 
   * @param REFERENCE $_REQUEST Array
   * @return $request
   */
	public function xssPrevent(&$request) { 
  	if (is_string($request)) {
	    $request = html_entity_decode($request);
	    $request = strip_tags($request);
	    $request = trim($request);
	    $request = stripslashes($request);
  	}
	  return $request;
  } // xssPrevent()
	
  /**
   * Action handler of the class
   * 
   * @return STR dialog or message
   */
  public function action() {
  	$html_allowed = array();
  	foreach ($_REQUEST as $key => $value) {
  		if (!in_array($key, $html_allowed)) {
  			$_REQUEST[$key] = $this->xssPrevent($value);	  			
  		} 
  	}
  	
  	//if (($this->params[self::param_server] == WB_URL) && (!isset($_SESSION[self::session_server_url]))) {
  	if (($this->server_url == WB_URL) && (!isset($_SESSION[self::session_server_url]))) {
  		// don't execute the droplet at the server!
  		// it is possible that the server param is replaced by the update process, so check the session too!
  		return '<div class="sync_data_inactive"></div>';
  	}
  	
    $action = isset($_REQUEST[self::request_action]) ? $_REQUEST[self::request_action] : self::action_default;
        
  	switch ($action):
  	case self::action_update_continue:
  		$result = $this->updateContinue();
  		break;
  	case self::action_update_start:
  		$result = $this->updateStart();
  		break;
  	case self::action_update_download:
  		$result = $this->updateDownload();
  		break;
  	case self::action_check_for_updates:
  		$result = $this->checkForUpdates();
  		break;
  	default:
  		$result = $this->dlgWelcome();
  		break;
  	endswitch;
  	
  	if ($this->isError()) {
  		$data = array('error' => $this->getError());
  		$result = $this->getTemplate('error.lte', $data);
  	}
		return $result;
  } // action
	
  /**
   * Save the desired $url to the path $save_to 
   * 
   * @param STR $url
   * @param STR $save_to
   * @return BOOL
   */
  public function saveURL($url, $save_to) {
  	if (ini_get('allow_url_fopen') == 1) {
  		if (false !== ($data = file_get_contents($url))) {
  			if (!file_put_contents($save_to, $data)) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_put_contents, $save_to)));
  				return false;
  			}
  		}
  		else {
  			$this->setError(sprintf('[%s %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_get_contents, $url)));
  			return false;
  		}
  	}
  	else {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sync_error_allow_url_fopen));
  		return false;
  	}
  	return true;
  } // saveURL()
  
  /**
   * Return the contents of the desired $url
   * 
   * @param STR $url
   * @return MIXED STR $data on success BOOL FALSE on error
   */
  public function getURL($url) {
  	if (ini_get('allow_url_fopen') == 1) {
  		if (false !== ($data = @file_get_contents($url))) {
  			return $data;
  		}
  		else {
  			$this->setError(sprintf('[%s %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_get_contents, $url)));
  			return false;
  		}
  	}
  	else {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sync_error_allow_url_fopen));
  		return false;
  	}
  } // getURL
  
  /**
   * Check if an internet connection is established
   * 
   * @param STR $url
   * @return BOOL $result
   */
  public function checkConnection($url) {
  	if (ini_get('allow_url_fopen') == 1) {
  		return (false !== ($data = file_get_contents($url))) ?	true : false;
  	}
  	else {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sync_error_allow_url_fopen));
  		return false;
  	}
  } // checkConnection()
  
  /**
   * Display a welcome dialog to the user
   * 
   * @return MIXED STR dialog on success BOOL FALSE on error
   */
	public function dlgWelcome() {
		//if (empty($this->params[self::param_server])) {
		if (empty($this->server_url)) {
			// es ist kein Server angegeben
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sync_error_param_missing_server));
			return false;
		}
		$data = array('action_link'	=> sprintf('%s?%s', $this->page_link, http_build_query(array(self::request_action => self::action_check_for_updates))));
		return $this->getTemplate('welcome.lte', $data);
	} // dlgWelcome()
	
	/**
	 * Check the desired Server URL wether updates exists or not
	 * 
	 * @return MIXED STR dialog on success or BOOL FALSE on error
	 */
	public function checkForUpdates() {
		global $dbSyncDataJob;
		
		//if (!$this->checkConnection($this->params[self::param_server])) {
		if (!$this->checkConnection($this->server_url)) {
			// es kann keine Verbindung zu dem Server aufgebaut werden
			$data = array(
				'server_url'		=> $this->server_url, //$this->params[self::param_server],
				'action_link'		=> sprintf('%s?%s', $this->page_link, http_build_query(array(self::request_action => self::action_check_for_updates)))
			);
			return $this->getTemplate('offline.lte', $data);
		}
		
		// get the main params for the archive file
		//if (false ===($response = $this->getURL(sprintf('%s/modules/sync_data/response.php?%s', $this->params[self::param_server], http_build_query(array(syncServer::request_action => syncServer::action_connect)))))) {
		if (false ===($response = $this->getURL(sprintf('%s/modules/sync_data/response.php?%s', $this->server_url, http_build_query(array(syncServer::request_action => syncServer::action_connect)))))) {
			$data = array('message' => sprintf(sync_msg_sync_connect_failed, $this->page_link));
			return $this->getTemplate('message.lte', $data);
		}
		// unserialize the request
		$request = unserialize($response);
		
		// check the status and message key of the response
		if (!isset($request[syncServer::result_status]) || !isset($request[syncServer::result_message])) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_sync_response_invalid, $response)));
			return false;
		}
		
		// syncData Server error?
		if ($request[syncServer::result_status] == syncServer::status_error) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $request[syncServer::result_message]));
			return false;
		}
		
		// all keys complete?
		if (!isset($request[syncServer::result_archive_file]) || !isset($request[syncServer::result_archive_id]) || 
				!isset($request[syncServer::result_archive_md5]) || !isset($request[syncServer::result_archive_number]) ||
				!isset($request[syncServer::result_archive_size]) || !isset($request[syncServer::result_archive_timestamp])) {
		  // missing keys in the $request array
		  $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sync_error_sync_missing_keys));
		  return false;
		}
		
		// check if the archive exists and get the last archive number
		$SQL = sprintf(	"SELECT * FROM %s WHERE %s='%s' AND %s='%s' ORDER BY %s DESC LIMIT 1",
										$dbSyncDataJob->getTableName(),
										dbSyncDataJobs::field_archive_id,
										$request[syncServer::result_archive_id],
										dbSyncDataJobs::field_status,
										dbSyncDataJobs::status_finished,
										dbSyncDataJobs::field_archive_number);
		$archive = array();
		if (!$dbSyncDataJob->sqlExec($SQL, $archive)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		if (count($archive) < 1) {
			// this archive does not exist (missing initial restore)
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_sync_missing_initial_restore, $request[syncServer::result_archive_id])));
			return false;
		}
		$archive = $archive[0];
		if ($archive[dbSyncDataJobs::field_archive_number] == $request[syncServer::result_archive_number]) {
			// this installation is up to date...
			$data = array();
			return $this->getTemplate('update.uptodate.lte', $data);
		}
		elseif ($archive[dbSyncDataJobs::field_archive_number]+1 == $request[syncServer::result_archive_number]) {
			// ok - this is the next update which should be installed
			return $this->dlgExecUpdate($request);
		}
		elseif ($archive[dbSyncDataJobs::field_archive_number] < $request[syncServer::result_archive_number]) {
			// missing one or more updates - load the next update!
			// get the main params for the archive file
			if (false ===($response = $this->getURL(sprintf('%s/modules/sync_data/response.php?%s', $this->server_url, //$this->params[self::param_server], 
										http_build_query(array(
											syncServer::request_action => syncServer::action_info,
											syncServer::request_archive_id => $request[syncServer::result_archive_id],
											syncServer::request_archive_number => $archive[dbSyncDataJobs::field_archive_number]+1)))))) {
				$data = array('message' => sprintf(sync_msg_sync_connect, $this->page_link));
				return $this->getTemplate('message.lte', $data);
			}
			// unserialize the request
			$request = unserialize($response);
			// check the status and message key of the response
			if (!isset($request[syncServer::result_status]) || !isset($request[syncServer::result_message])) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_sync_response_invalid, $response)));
				return false;
			}
			// syncData Server error?
			if ($request[syncServer::result_status] == syncServer::status_error) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $request[syncServer::result_message]));
				return false;
			}		
			// all keys complete?
			if (!isset($request[syncServer::result_archive_file]) || !isset($request[syncServer::result_archive_id]) || 
					!isset($request[syncServer::result_archive_md5]) || !isset($request[syncServer::result_archive_number]) ||
					!isset($request[syncServer::result_archive_size]) || !isset($request[syncServer::result_archive_timestamp])) {
				// missing keys in the $request array
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sync_error_sync_missing_keys));
		    return false;
			}
			// set SESSION to mark that a further update is available!
			$_SESSION[self::session_further_update] = true;
			return $this->dlgExecUpdate($request);			
		}
		else {
			// Oooops - data corrupt?
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sync_error_sync_data_corrupt, $request[syncServer::request_archive_id]));
			return false;
		}
		
	} // checkForUpdates()
	
	/**
	 * Exec an dialog for processing an available update
	 * 
	 * @param ARRAY $request
	 * @return STR dialog
	 */
	public function dlgExecUpdate($request) {
		$_SESSION[self::session_server_request] = $request;
		$_SESSION[self::session_server_url] = $this->server_url; //$this->params[self::param_server];
		$data = array(
			'action_link' => sprintf('%s?%s', $this->page_link, http_build_query(array(self::request_action => self::action_update_download))),
			'img_url'			=> $this->image_url
		);
		return $this->getTemplate('update.available.lte', $data);
	} // execUpdate()
	
	/**
	 * Process the download from update server and check the MD5 of the archive
	 * 
	 * @return MIXED STR update dialog or BOOL FALSE on error
	 */
	public function updateDownload() {
		$request = $_SESSION[self::session_server_request];
		
		// download the archive file from syncServer to the TEMP directory
		//if (!$this->saveURL(sprintf('%s/media/sync_data/backup/%s', $this->params[self::param_server], $request[syncServer::result_archive_file]), $this->temp_path.$request[syncServer::result_archive_file])) {
		if (!$this->saveURL(sprintf('%s/media/sync_data/backup/%s', $this->server_url, $request[syncServer::result_archive_file]), $this->temp_path.$request[syncServer::result_archive_file])) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_sync_download_archive_file, $reques[syncServer::result_archive_file])));
			return false;
		}
		
		if (false === ($md5 = md5_file($this->temp_path.$request[syncServer::result_archive_file]))) {
			// error getting md5 checksum
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_sync_archive_file_get_md5, $request[syncServer::result_archive_file])));
			return false;
		}
		
		if ($md5 != $request[syncServer::result_archive_md5]) {
			// md5 checksum differ!
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_sync_md5_checksum_differ, $request[syncServer::result_archive_file])));
			// delete archive from TEMP dir, ignore possible errors
			@unlink($this->temp_path.$request[syncServer::result_archive_file]);
			return false;
		}
		
		// ok - all checks done, archive is valid, move it to the regular directory
		if (!file_exists(WB_PATH.'/media/sync_data/backup')) {
			if (!mkdir(WB_PATH.'/media/sync_data/backup', 0755, true)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_mkdir, '/media/sync_data/backup')));
				return false;
			}
		}
		if (!rename($this->temp_path.$request[syncServer::result_archive_file], WB_PATH.'/media/sync_data/backup/'.$request[syncServer::result_archive_file])) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_rename, $request[syncServer::result_archive_file])));
			return false; 
		}
	
		$data = array(
			'action_link' => sprintf('%s?%s', $this->page_link, http_build_query(array(self::request_action => self::action_update_start))),
			'img_url'			=> $this->image_url
		);
		return $this->getTemplate('update.start.lte', $data);
	} // updateDownload()
	
	/**
	 * Start the update process with the available update archive
	 * 
	 * @return MIXED STR process dialog or BOOL FALSE on error
	 */
	public function updateStart() {
		global $interface;
		
		$request = $_SESSION[self::session_server_request];
		$backup_archive = '/media/sync_data/backup/'.$request[syncServer::result_archive_file];
		
		// get the content of sync_data.ini into the $ini_data array
  	$ini_data = array();
  	if (!$interface->restoreInfo($backup_archive, $ini_data)) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $interface->getError()));
  		return false;
  	}
  	
  	// existiert die Dateiliste im /temp Verzeichnis?
  	if (false === ($list = unserialize(file_get_contents($this->temp_path.'/sync_data/'.syncDataInterface::archive_list)))) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_read, $this->temp_path.self::archive_list)));
  		return false;
  	}
  	
  	// pruefen ob Dateien wiederhergestellt werden sollen
  	$restore_info = $interface->array_search($list, 'filename', 'files/', true);
  	$restore_files = (count($restore_info) > 0) ? true : false;
  	$restore_info = $interface->array_search($list, 'filename', 'sql/', true);
  	$restore_tables = (count($restore_info) > 0) ? true : false;
  	
  	$type = array();
  	if ($restore_files) {
  		$type[] = dbSyncDataJobs::type_restore_files;
  	}
  	if ($restore_tables) {
  		$type[] = dbSyncDataJobs::type_restore_mysql;
  	}
  	
  	// gettting the params for restoring
  	$replace_prefix 			= true;
  	$replace_url					= true;
  	$restore_mode					= dbSyncDataJobs::mode_changed_date_size;
  	$restore_type					= $type;
  	$ignore_config				= true;
  	$ignore_htaccess			= true;
  	$delete_files					= false;
  	$delete_tables				= false;
  	
  	$job_id = -1;
  	$status = $interface->restoreStart($backup_archive, $replace_prefix, $replace_url, $restore_type, $restore_mode, $ignore_config, $ignore_htaccess, $delete_files, $delete_tables, $job_id); 
  	if ($interface->isError()) {
  		// error executing interface
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $interface->getError()));
  		return false;
  	}
  	
  	if ($status == dbSyncDataJobs::status_time_out) {
  		// interrupt restore
			return $this->updateInterrupt($job_id);
		}
		elseif ($status == dbSyncDataJobs::status_finished) {
			// finish restore
			return $this->updateFinished($job_id);
		}
		else {
			// unknown status ...
			$this->setError(sprintf('[%s %s] %s', __METHOD__, __LINE__, sync_error_status_unknown));
			return false;
		}
	} // updateStart()

	/**
	 * Interrupt the update process to prevent timeouts and display a dialog for continue the process
	 * 
	 * @param INT $job_id
	 * @return STR continue dialog
	 */
	public function updateInterrupt($job_id) {
		$data = array(
			'img_url'			=> $this->image_url,
			'action_link'	=> sprintf('%s?%s', $this->page_link, http_build_query(array(self::request_action => self::action_update_continue, self::request_job_id => $job_id)))
		);
		return $this->getTemplate('update.interrupt.lte', $data);
	} // updateInterrupt()
	
	/**
	 * Continue an interrupted update process
	 * 
	 * @return MIXED STR process dialog or BOOL FALSE on error
	 */
	public function updateContinue() {
		global $interface;
		
		$job_id = isset($_REQUEST[self::request_job_id]) ? $_REQUEST[self::request_job_id] : -1;
  	
  	if ($job_id < 1) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sync_error_job_id_invalid));
  		return false;
  	}
  	
		$status = $interface->restoreContinue($job_id);  
  	if ($interface->isError()) { 
  		// error executing interface
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $interface->getError()));
  		return false;
  	}
  	if ($status == dbSyncDataJobs::status_time_out) {
  		// interrupt restore
			return $this->updateInterrupt($job_id);
		}
		elseif ($status == dbSyncDataJobs::status_finished) {
			// finish restore
			return $this->updateFinished($job_id);
		}
		else {
			// unknown status ...
			$this->setError(sprintf('[%s %s] %s', __METHOD__, __LINE__, sync_error_status_unknown));
			return false;
		}	
	} // updateContinue()
	
	/**
	 * Display the finish dialog and give the user the information if a further
	 * update is available.
	 * 
	 * @param INT $job_id
	 * @return MIXED STR finish dialog or BOOL FALSE on error
	 */
	public function updateFinished($job_id) {
		global $database;
		/**
		 * very important: all server addresses will be replaced by the adress of the client,
		 * so the sync_client droplet is too rewritten and must be corrected!
		 */ 
		$SQL = sprintf( "SELECT * FROM %smod_wysiwyg WHERE page_id='%s' AND (content LIKE '%%[[sync_client%%')",
										TABLE_PREFIX,
										PAGE_ID);
		$query = $database->query($SQL);
		if (false !== ($section = $query->fetchRow(MYSQL_ASSOC))) { 
			$content = $section['content'];
			if (false !== strpos($content, '[[sync_client]]')) { 
				$content = str_replace('[[sync_client]]', sprintf('[[sync_client?server=%s]]', $_SESSION[self::session_server_url]), $content);
			}
			else {
				$content = str_replace(sprintf('[[sync_client?server=%s]]', WB_URL), sprintf('[[sync_client?server=%s]]', $_SESSION[self::session_server_url]), $content);
			}
			$SQL = sprintf( "UPDATE %smod_wysiwyg SET content='%s', text='%s' WHERE section_id='%s'", 
											TABLE_PREFIX,
											$content,
											strip_tags($content),
											$section['section_id']);
			$database->query($SQL);
			if ($database->is_error()) echo $database->get_error();
		}
		
		$data = array(
			'img_url'						=> $this->image_url,
			'action_link'				=> sprintf('%s?%s', $this->page_link, http_build_query(array(self::request_action => self::action_check_for_updates))),
			'update_available'	=> (isset($_SESSION[self::session_further_update])) ? 1 : 0
		);
		unset($_SESSION[self::session_further_update]);
		unset($_SESSION[self::session_server_request]);
		unset($_SESSION[self::session_server_url]);
		return $this->getTemplate('update.finished.lte', $data);
	} // updateFinished()
	
} // class syncClient

?>