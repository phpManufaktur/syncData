<?php

/**
 * syncData
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id: class.backend.php 8 2011-08-19 01:26:33Z phpmanufaktur $
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

// include language file for syncData
if(!file_exists(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php')) {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/EN.php');  
}
else {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php'); 
}

require_once WB_PATH.'/modules/pclzip/pclzip.lib.php';

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

require_once WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.syncdata.php';
require_once WB_PATH.'/framework/functions.php';
require_once WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.interface.php';

class syncBackend {
	
	const request_action							= 'act';
	const request_file_backup_start		= 'bus';
	const request_items								= 'its';
	const request_backup							= 'bak';
	const request_restore							= 'rst';
	const request_restore_continue		= 'rstc';
	const request_restore_process			= 'rstp';
	const request_restore_replace_url = 'rstru';
	const request_restore_replace_prefix = 'rstrp';
	const request_restore_type				= 'rstt';
	
	const action_about								= 'abt';
	const action_config								= 'cfg';
	const action_config_check					= 'cfgc';
	const action_default							= 'def';
	const action_backup								= 'back';
	const action_backup_start					= 'baks';
	const action_backup_start_new			= 'baksn';
	const action_backup_continue			= 'bakc';
	const action_process_backup				= 'pb';
	const action_restore							= 'rst';
	const action_restore_continue			= 'rstc';
	const action_restore_info					= 'rsti';
	const action_restore_start				= 'rsts';
	const action_update_continue			= 'updc';
	const action_update_start					= 'upds';
	
	private $tab_navigation_array = array(
		self::action_backup							=> sync_tab_backup,
		self::action_restore						=> sync_tab_restore,
		self::action_config							=> sync_tab_cfg,
		self::action_about							=> sync_tab_about		
	);
	
	const add_max_rows								= 5;
	
	private $page_link 								= '';
	private $img_url									= '';
	private $template_path						= '';
	private $error										= '';
	private $message									= '';
	private $temp_path								= '';
	private $max_execution_time				= 30;
	private $limit_execution_time			= 25;
	private $memory_limit							= '256M'; 
	private $next_file								= ''; 
	
	/**
	 * Constructor
	 */
	public function __construct() {
		global $dbSyncDataCfg;
		$this->page_link = ADMIN_URL.'/admintools/tool.php?tool=sync_data';
		$this->template_path = WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/templates/' ;
		$this->img_url = WB_URL. '/modules/'.basename(dirname(__FILE__)).'/images/';
		date_default_timezone_set(sync_cfg_time_zone);
		$this->temp_path = WB_PATH.'/temp/sync_data/';
		if (!file_exists($this->temp_path)) mkdir($this->temp_path, 0755, true);
		$this->memory_limit = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgMemoryLimit);
		ini_set("memory_limit",$this->memory_limit);
		$this->max_execution_time = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgMaxExecutionTime);
		$this->limit_execution_time = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgLimitExecutionTime);
		set_time_limit($this->max_execution_time);
	} // __construct()
	
	/**
    * Set $this->error to $error
    * 
    * @param STR $error
    */
  public function setError($error) {
  	$debug = debug_backtrace();
    $caller = next($debug);
  	$this->error = sprintf('[%s::%s - %s] %s', basename($caller['file']), $caller['function'], $caller['line'], $error);
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
    $result = '';
  	try {
  		$result = $parser->get($this->template_path.$template, $template_data); 
  	} catch (Exception $e) {
  		$this->setError(sprintf(sync_error_template_error, $template, $e->getMessage()));
  		return false;
  	}
  	return $result;
  } // getTemplate()
  
  
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
    isset($_REQUEST[self::request_action]) ? $action = $_REQUEST[self::request_action] : $action = self::action_default;
        
  	switch ($action):
  	case self::action_about:
  		$this->show(self::action_about, $this->dlgAbout());
  		break;
  	case self::action_config:
  		$this->show(self::action_config, $this->dlgConfig());
  		break;
  	case self::action_config_check:
  		$this->show(self::action_config, $this->checkConfig());
  		break;
  	case self::action_process_backup:
  		$this->show(self::action_backup, $this->processBackup());
  		break;
  	case self::action_backup_start:
  		$this->show(self::action_backup, $this->dlgBackupStart());
  		break;
  	case self::action_backup_start_new:
  		$this->show(self::action_backup, $this->backupStartNewArchive());
  		break;
  	case self::action_backup_continue:
  		$this->show(self::action_backup, $this->backupContinue());
  		break;
  	case self::action_restore:
  		$this->show(self::action_restore, $this->dlgRestore());
  		break;
  	case self::action_restore_info:
  		$this->show(self::action_restore, $this->restoreInfo());
  		break;
  	case self::action_restore_start:
  		$this->show(self::action_restore, $this->restoreStart());
  		break;
  	case self::action_restore_continue:
  		$this->show(self::action_restore, $this->restoreContinue());
  		break;
  	case self::action_update_start:
  		$this->show(self::action_backup, $this->updateStart());
  		break;
  	case self::action_update_continue:
  		$this->show(self::action_backup, $this->updateContinue());
  		break;
  	case self::action_default:
  	default:
  		$this->show(self::action_backup, $this->dlgBackup());
  		break;
  	endswitch;
  } // action
	
  	
  /**
   * Ausgabe des formatierten Ergebnis mit Navigationsleiste
   * 
   * @param STR $action - aktives Navigationselement
   * @param STR $content - Inhalt
   * 
   * @return ECHO RESULT
   */
  public function show($action, $content) {
  	$navigation = array();
  	foreach ($this->tab_navigation_array as $key => $value) {
  		$navigation[] = array(
  			'active' 	=> ($key == $action) ? 1 : 0,
  			'url'			=> sprintf('%s&%s=%s', $this->page_link, self::request_action, $key),
  			'text'		=> $value
  		);
  	}
  	$data = array(
  		'WB_URL'			=> WB_URL,
  		'navigation'	=> $navigation,
  		'error'				=> ($this->isError()) ? 1 : 0,
  		'content'			=> ($this->isError()) ? $this->getError() : $content
  	);
  	echo $this->getTemplate('backend.body.lte', $data); echo $this->getError();
  } // show()
	
  /**
   * About Dialog
   * 
   * @return STR dialog
   */
  public function dlgAbout() {
  	$data = array(
  		'version'					=> sprintf('%01.2f', $this->getVersion()),
  		'img_url'					=> $this->img_url.'/sync_data_logo.png',
  		'release_notes'		=> file_get_contents(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/info.txt'),
  	);
  	return $this->getTemplate('backend.about.lte', $data);
  } // dlgAbout()
  
	
  /**
   * Dialog zur Konfiguration und Anpassung von syncData
   * 
   * @return STR dialog
   */
  public function dlgConfig() {
		global $dbSyncDataCfg;
		global $dbSyncDataArchive;
		
		$SQL = sprintf(	"SELECT * FROM %s WHERE NOT %s='%s' ORDER BY %s",
										$dbSyncDataCfg->getTableName(),
										dbSyncDataCfg::field_status,
										dbSyncDataCfg::status_deleted,
										dbSyncDataCfg::field_name);
		$config = array();
		if (!$dbSyncDataCfg->sqlExec($SQL, $config)) {
			$this->setError($dbSyncDataCfg->getError());
			return false;
		}
		$count = array();
		$header = array(
			'identifier'	=> sync_header_cfg_identifier,
			'value'				=> sync_header_cfg_value,
			'description'	=> sync_header_cfg_description
		);
		
		$items = array();
		// bestehende Eintraege auflisten
		foreach ($config as $entry) {
			$id = $entry[dbSyncDataCfg::field_id];
			$count[] = $id;
			$value = ($entry[dbSyncDataCfg::field_type] == dbSyncDataCfg::type_list) ? $dbSyncDataCfg->getValue($entry[dbSyncDataCfg::field_name]) : $entry[dbSyncDataCfg::field_value];
			if (isset($_REQUEST[dbSyncDataCfg::field_value.'_'.$id])) $value = $_REQUEST[dbSyncDataCfg::field_value.'_'.$id];
			if ($entry[dbSyncDataCfg::field_name] == dbSyncDataCfg::cfgServerArchiveID) {
				// Archiv IDs auslesen
				$is_value = $value;
				$value = array();
				$value[] = array(
					'value'			=> '',
					'selected'	=> ($is_value == '') ? 1 : 0,
					'text'			=> '- select -'
				);
				$where = array(
					dbSyncDataArchives::field_status					=> dbSyncDataArchives::status_active,
					dbSyncDataArchives::field_archive_number	=> 1
				);
				$archives = array();
				if (!$dbSyncDataArchive->sqlSelectRecord($where, $archives)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataArchive->getError()));
					return false;
				}
				foreach ($archives as $archive) {
					$value[] = array(
						'value'			=> $archive[dbSyncDataArchives::field_archive_id],
						'selected'	=> ($is_value == $archive[dbSyncDataArchives::field_archive_id]) ? 1 : 0,
						'text'			=> sprintf('[ %s ] %s', $archive[dbSyncDataArchives::field_archive_id], $archive[dbSyncDataArchives::field_archive_name])
					);
				}
			}
			else {
				$value = str_replace('"', '&quot;', stripslashes($value));
			}
			$items[] = array(
				'id'					=> $id,
				'identifier'	=> constant($entry[dbSyncDataCfg::field_label]),
				'value'				=> $value,
				'name'				=> sprintf('%s_%s', dbSyncDataCfg::field_value, $id),
				'description'	=> constant($entry[dbSyncDataCfg::field_description]),
				'type'				=> $dbSyncDataCfg->type_array[$entry[dbSyncDataCfg::field_type]],
				'field'				=> $entry[dbSyncDataCfg::field_name]
			);
		}
		$data = array(
			'form_name'						=> 'flex_table_cfg',
			'form_action'					=> $this->page_link,
			'action_name'					=> self::request_action,
			'action_value'				=> self::action_config_check,
			'items_name'					=> self::request_items,
			'items_value'					=> implode(",", $count), 
			'head'								=> sync_header_cfg,
			'intro'								=> $this->isMessage() ? $this->getMessage() : sprintf(sync_intro_cfg, 'syncData'),
			'is_message'					=> $this->isMessage() ? 1 : 0,
			'items'								=> $items,
			'btn_ok'							=> sync_btn_ok,
			'btn_abort'						=> sync_btn_abort,
			'abort_location'			=> $this->page_link,
			'header'							=> $header
		);
		return $this->getTemplate('backend.config.lte', $data);
	} // dlgConfig()
	
	/**
	 * Ueberprueft Aenderungen die im Dialog dlgConfig() vorgenommen wurden
	 * und aktualisiert die entsprechenden Datensaetze.
	 * 
	 * @return STR DIALOG dlgConfig()
	 */
	public function checkConfig() {
		global $dbSyncDataCfg;
		$message = '';
		// ueberpruefen, ob ein Eintrag geaendert wurde
		if ((isset($_REQUEST[self::request_items])) && (!empty($_REQUEST[self::request_items]))) {
			$ids = explode(",", $_REQUEST[self::request_items]);
			foreach ($ids as $id) {
				if (isset($_REQUEST[dbSyncDataCfg::field_value.'_'.$id])) {
					$value = $_REQUEST[dbSyncDataCfg::field_value.'_'.$id];
					$where = array();
					$where[dbSyncDataCfg::field_id] = $id; 
					$config = array();
					if (!$dbSyncDataCfg->sqlSelectRecord($where, $config)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataCfg->getError()));
						return false;
					}
					if (sizeof($config) < 1) {
						$this->setError(sprintf(sync_error_cfg_id, $id));
						return false;
					}
					$config = $config[0];
					if ($config[dbSyncDataCfg::field_value] != $value) {
						// Wert wurde geaendert
							if (!$dbSyncDataCfg->setValue($value, $id) && $dbSyncDataCfg->isError()) {
								$this->setError($dbSyncDataCfg->getError());
								return false;
							}
							elseif ($dbSyncDataCfg->isMessage()) {
								$message .= $dbSyncDataCfg->getMessage();
							}
							else {
								// Datensatz wurde aktualisiert
								$message .= sprintf(sync_msg_cfg_id_updated, $config[dbSyncDataCfg::field_name]);
							}
					}
					unset($_REQUEST[dbSyncDataCfg::field_value.'_'.$id]);
				}
			}		
		}		
		$this->setMessage($message);
		return $this->dlgConfig();
	} // checkConfig()

	
	/**
	 * Dialog: select existing or new backup
	 * 
	 * @return STR dialog
	 */
	public function dlgBackup() {
		global $dbSyncDataArchive;

		$SQL = sprintf( "SELECT * FROM %s WHERE %s='1' AND %s='%s'",
										$dbSyncDataArchive->getTableName(),
										dbSyncDataArchives::field_archive_number,
										dbSyncDataArchives::field_status,
										dbSyncDataArchives::status_active);
		$archives = array();								
		if (!$dbSyncDataArchive->sqlExec($SQL, $archives)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataArchive->getError()));
			return false;
		}
		$select_array = array();
		$select_array[] = array(
			'value'			=> -1,
			'selected'	=> 1,
			'text'			=> sync_str_new_backup
		);
		foreach ($archives as $archive) {
			$select_array[] = array(
				'value'			=> $archive[dbSyncDataArchives::field_archive_id],
				'selected'	=> 0,
				'text'			=> sprintf('%s - %s', date(sync_cfg_datetime_str, strtotime($archive[dbSyncDataArchives::field_archive_date])), $archive[dbSyncDataArchives::field_archive_name])
			);
		}
		
		$data = array(
			'form'			=> array(	'name'		=> 'backup_select',
														'link'		=> $this->page_link,
														'action'	=> array( 'name'	=> self::request_action,
																								'value'	=> self::action_backup_start),
														'btn'			=> array(	'ok'		=> sync_btn_ok)
														),
			'backup'		=> array(	'name'		=> self::request_backup,
														'label'		=> sync_label_backup_select,
														'hint'		=> sync_hint_backup_select,
														'options'	=> $select_array),
			'head'			=> sync_header_backup,
			'is_intro'	=> $this->isMessage() ? 0 : 1,
			'intro'			=> $this->isMessage() ? $this->getMessage() : sync_intro_backup
		);
		return $this->getTemplate('backend.backup.select.lte', $data);
	} // dlgBackup()
	
	/**
	 * Dialog zur Auswahl eines neuen Backup oder zur Aktualisierung eines
	 * bestehenden Backup
	 * 
	 * @return STR dialog
	 */
	public function dlgBackupStart()  {
		global $dbSyncDataArchive;
		global $dbSyncDataFile;
		global $kitTools;
		global $dbSyncDataCfg;
		
		$archiv_id = isset($_REQUEST[self::request_backup]) ? $_REQUEST[self::request_backup] : -1;
		
		if ($archiv_id == -1) {
			// neues Archiv anlegen
			$select_array = array();
			foreach ($dbSyncDataArchive->backup_type_array as $type) {
				$select_array[] = array(
					'value'			=> $type['key'],
					'text'			=> $type['value'],
					'selected'	=> ($type['key'] == dbSyncDataArchives::backup_type_complete) ? 1 : 0
				);
			}
			$data = array(
				'form'			=> array(	'name'		=> 'backup_start',
															'link'		=> $this->page_link,
															'action'	=> array( 'name'	=> self::request_action,
																									'value'	=> self::action_backup_start_new),
															'btn'			=> array(	'ok'		=> sync_btn_ok)
															),
				'backup_type' => array('name'		=> dbSyncDataArchives::field_backup_type,
															'label'		=> sync_label_backup_type_select,
															'hint'		=> sync_hint_backup_type_select,
															'options'	=> $select_array),
			  'archive_name' => array('name'	=> dbSyncDataArchives::field_archive_name,
															'value'		=> '',
															'label'		=> sync_label_archive_name,
															'hint'		=> sync_hint_archive_name),
				'head'			=> sync_header_backup_new,
				'is_intro'	=> $this->isMessage() ? 0 : 1,
				'intro'			=> $this->isMessage() ? $this->getMessage() : sync_intro_backup_new,
				'text_process' 	=> sprintf(sync_msg_backup_running, $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgLimitExecutionTime)),
				'img_url'		=> $this->img_url									
			);
			return $this->getTemplate('backend.backup.new.lte', $data);
		}
		else {
			/**
			 * The backup archive should be updated
			 */
			
			// first step: read the archive informations
			$where = array(dbSyncDataArchives::field_archive_id => $archiv_id);
			$archive = array();
			if (!$dbSyncDataArchive->sqlSelectRecord($where, $archive)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataArchive->getError()));
				return false;
			}
			if (count($archive) < 1) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_archive_id_invalid, $archiv_id)));
				return false;
			}
			$archive = $archive[0];
			
			// second step: gather the informations about the archived tables and files
			$SQL = sprintf( "SELECT COUNT(%s) AS count, SUM(%s) AS bytes FROM %s WHERE %s='%s' AND %s='%s' AND %s='%s'",
											dbSyncDataFiles::field_file_name,
											dbSyncDataFiles::field_file_size,
											$dbSyncDataFile->getTableName(),
											dbSyncDataFiles::field_archive_id,
											$archive[dbSyncDataArchives::field_archive_id],
											dbSyncDataFiles::field_archive_number,
											$archive[dbSyncDataFiles::field_archive_number],
											dbSyncDataFiles::field_status,
											dbSyncDataFiles::status_ok);
			$files = array();
			if (!$dbSyncDataFile->sqlExec($SQL, $files)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
				return false;
			}
			if (count($files) < 1) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sync_error_file_list_invalid));
				return false;
			}
			$files = $files[0];
			
	  	$values = array(
	  		array('label'	=> sync_label_archive_id, 		'text' => $archive[dbSyncDataArchives::field_archive_id]),
	  		array('label'	=> sync_label_archive_number, 'text' => $archive[dbSyncDataArchives::field_archive_number]),
	  		array('label' => sync_label_archive_type, 	'text' => $dbSyncDataArchive->backup_type_array_text[$archive[dbSyncDataArchives::field_archive_type]]),
	  		array('label' => sync_label_total_files, 		'text' => $files['count']),
	  		array('label' => sync_label_total_size, 		'text' => $kitTools->bytes2Str($files['bytes'])),
	  		array('label' => sync_label_timestamp, 			'text' => date(sync_cfg_datetime_str, strtotime($archive[dbSyncDataArchives::field_timestamp])))
	  	);
	  	$info = array(
	  		'label'		=> sync_label_archive_info,
	  		'values'	=> $values
	  	);
	  	
			$data = array(
				'form'			=> array(	'name'		=> 'backup_update',
															'link'		=> $this->page_link,
															'action'	=> array( 'name'	=> self::request_action,
																									'value'	=> self::action_update_start),
															'archive'	=> array(	'name'	=> dbSyncDataArchives::field_archive_id,
																									'value'	=> $archiv_id),
															'btn'			=> array(	'ok'		=> sync_btn_ok,
																									'abort'	=> sync_btn_abort)
															),
				'info'			=> $info,
				'archive_name' => array('name'	=> dbSyncDataArchives::field_archive_name,
															'value'		=> '',
															'label'		=> sync_label_archive_name,
															'hint'		=> sync_hint_archive_name),
				'head'			=> sync_header_backup_update,
				'is_intro'	=> $this->isMessage() ? 0 : 1,
				'intro'			=> $this->isMessage() ? $this->getMessage() : sync_intro_backup_update,
				'text_process' 	=> sprintf(sync_msg_backup_running, $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgLimitExecutionTime)),
				'img_url'		=> $this->img_url									
			);
			return $this->getTemplate('backend.backup.update.lte', $data);
		}		
	} // dlgBackupStart()
	
	
	/**
	 * Legt ein neues Backup Archiv an und startet die Datensicherung
	 * 
	 * @return STR Dialog zum Fortsetzen/Beenden oder BOOL FALSE on error 
	 */
	public function backupStartNewArchive() {
		global $interface;
		
		$backup_name = (isset($_REQUEST[dbSyncDataArchives::field_archive_name]) && !empty($_REQUEST[dbSyncDataArchives::field_archive_name])) ? $_REQUEST[dbSyncDataArchives::field_archive_name] : sprintf(sync_str_backup_default_name, date(sync_cfg_datetime_str));
		$backup_type = (isset($_REQUEST[dbSyncDataArchives::field_backup_type])) ? $_REQUEST[dbSyncDataArchives::field_backup_type] : dbSyncDataArchives::backup_type_complete;
		
		$job_id = -1;
		$status = $interface->backupStart($backup_name, $backup_type, $job_id);
		if ($interface->isError()) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $interface->getError()));
			return false; 
		}
		
		if ($status == dbSyncDataJobs::status_time_out) {
			return $this->messageBackupInterrupt($job_id);
		}
		elseif ($status == dbSyncDataJobs::status_finished) {
			return $this->messageBackupFinished($job_id);
		}
		else {
			// unknown status ...
			$this->setError(sprintf('[%s %s] %s', __METHOD__, __LINE__, sync_error_status_unknown));
			return false;
		}
	} // backupStartNewArchive()
	
	/**
	 * Generate and show a message that the backup is interrupted,
	 * shows the actual state of backup
	 * 
	 * @param INT $job_id
	 * @return STR message dialog
	 */
	public function messageBackupInterrupt($job_id) {
		global $dbSyncDataJob;
		global $dbSyncDataFile;
		global $kitTools;
		global $dbSyncDataCfg;
		
		$where = array(dbSyncDataJobs::field_id => $job_id);
		$job = array();
		if (!$dbSyncDataJob->sqlSelectRecord($where, $job)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		$job = $job[0];
		
		// Anzahl und Umfang der bisher gesicherten Dateien ermitteln
		$SQL = sprintf(	"SELECT COUNT(%s) AS count, SUM(%s) AS bytes FROM %s WHERE %s='%s' AND %s='%s' AND %s='%s'",
										dbSyncDataFiles::field_file_name,
										dbSyncDataFiles::field_file_size,
										$dbSyncDataFile->getTableName(),
										dbSyncDataFiles::field_archive_id,
										$job[dbSyncDataJobs::field_archive_id], //$archive_id,
										dbSyncDataFiles::field_status,
										dbSyncDataFiles::status_ok,
										dbSyncDataFiles::field_action,
										dbSyncDataFiles::action_add);
		$files = array();
		if (!$dbSyncDataFile->sqlExec($SQL, $files)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
			return false;
		}
		$auto_exec_msec = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgAutoExecMSec);
		$auto_exec = $auto_exec_msec > 0 ? sprintf(sync_msg_auto_exec_msec, $auto_exec_msec) : '';
		$info = sprintf(	sync_msg_backup_to_be_continued,
											$this->max_execution_time,
											$files[0]['count'],
											$kitTools->bytes2Str($files[0]['bytes']),
											$auto_exec );
		$data = array(
			'form'			=> array(	'name'		=> 'backup_continue',
														'link'		=> $this->page_link,
														'action'	=> array(	'name'	=> self::request_action,
																								'value'	=> self::action_backup_continue),
														'btn'			=> array(	'abort'	=> sync_btn_abort,
																								'ok'		=> sync_btn_continue)),
			'head'			=> sync_header_backup_continue,
			'is_intro'	=> $this->isMessage() ? 0 : 1,
			'intro'			=> $this->isMessage() ? $this->getMessage() : $info,
			'job'				=> array(	'name'	=> dbSyncDataJobs::field_id,
														'value'	=> $job_id),
			'text_process' 	=> sprintf(sync_msg_backup_running, $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgLimitExecutionTime)),
			'img_url'		=> $this->img_url,
			'auto_exec_msec' => $auto_exec_msec
		);
		// Statusmeldung ausgeben
		return $this->getTemplate('backend.backup.interrupt.lte', $data);
	} // messageBackupInterrupt()
		
	/**
	 * Generate and show a message that the backup is finished.
	 * Shows the main stats of the backup
	 * 
	 * @param INT $job_id
	 * @return STR message dialog
	 */
	public function messageBackupFinished($job_id) {
		global $dbSyncDataJob;
		global $dbSyncDataFile;
		global $kitTools;
		global $interface;
		global $dbSyncDataArchive;
		
		$where = array(dbSyncDataJobs::field_id => $job_id);
		$job = array();
		if (!$dbSyncDataJob->sqlSelectRecord($where, $job)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		if (count($job) < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_job_id_invalid, $job_id)));
			return false;
		}
		$job = $job[0];
		
		$where = array(	dbSyncDataArchives::field_archive_id 			=> $job[dbSyncDataJobs::field_archive_id],
										dbSyncDataArchives::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number]);
		$archive = array();
		if (!$dbSyncDataArchive->sqlSelectRecord($where, $archive)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataArchive->getError()));
			return false;
		}
		if (count($archive) < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_archive_id_invalid, $job[dbSyncDataJobs::field_archive_id])));
			return false;
		}
		$archive = $archive[0];
		
		// Anzahl und Umfang der bisher gesicherten Dateien ermitteln
		$SQL = sprintf(	"SELECT COUNT(%s), SUM(%s) FROM %s WHERE %s='%s' AND %s='%s' AND %s='%s'",
										dbSyncDataFiles::field_file_name,
										dbSyncDataFiles::field_file_size,
										$dbSyncDataFile->getTableName(),
										dbSyncDataFiles::field_archive_id,
										$job[dbSyncDataJobs::field_archive_id],
										dbSyncDataFiles::field_status,
										dbSyncDataFiles::status_ok,
										dbSyncDataFiles::field_action,
										dbSyncDataFiles::action_add);
		$files = array();
		if (!$dbSyncDataFile->sqlExec($SQL, $files)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
			return false;
		}
		
		// Meldung zusammenstellen
		$info = sprintf(	sync_msg_backup_finished,
											$files[0][sprintf('COUNT(%s)', dbSyncDataFiles::field_file_name)],
											$kitTools->bytes2Str($files[0][sprintf('SUM(%s)', dbSyncDataFiles::field_file_size)]),
											str_replace(WB_PATH, WB_URL, $interface->getBackupPath().$archive[dbSyncDataArchives::field_archive_name].'.zip'),
											str_replace(WB_PATH, WB_URL, $interface->getBackupPath().$archive[dbSyncDataArchives::field_archive_name].'.zip') );
		$data = array(
			'form'			=> array(	'name'		=> 'backup_continue',
														'link'		=> $this->page_link,
														'action'	=> array(	'name'	=> self::request_action,
																								'value'	=> self::action_default),
														'btn'			=> array(	'ok'		=> sync_btn_ok)),
			'head'			=> sync_header_backup_finished,
			'is_intro'	=> $this->isMessage() ? 0 : 1,
			'intro'			=> $this->isMessage() ? $this->getMessage() : $info,
			'job'				=> array(	'name'	=> dbSyncDataJobs::field_id,
														'value'	=> $job_id)
		);
		// Statusmeldung ausgeben
		return $this->getTemplate('backend.backup.message.lte', $data);
		
	} // messageBackupFinished()
	
	
	/*
	 * Nimmt das Backup nach einer Unterbrechung wieder auf
	 * 
	 * @return STR Dialog bzw. Statusmeldung
	 */
	public function backupContinue() {
		global $interface;
		
		$job_id = isset($_REQUEST[dbSyncDataJobs::field_id]) ? $_REQUEST[dbSyncDataJobs::field_id] : -1;
		
		if ($job_id < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_job_id_invalid, $job_id)));
			return false;
		}
		
		$status = $interface->backupContinue($job_id);
		if ($interface->isError()) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $interface->getError()));
			return false;
		}
		if ($status == dbSyncDataJobs::status_time_out) {
			return $this->messageBackupInterrupt($job_id);
		}
		elseif ($status == dbSyncDataJobs::status_finished) {
			return $this->messageBackupFinished($job_id);
		}
		else {
			// in allen anderen Faellen ist nichts zu tun
			$this->setMessage(sync_msg_nothing_to_do);
			$data = array(
				'form'			=> array(	'name'		=> 'backup_stop',
															'link'		=> $this->page_link,
															'action'	=> array(	'name'	=> self::request_action,
																									'value'	=> self::action_default),
															'btn'			=> array(	'abort'	=> sync_btn_abort,
																									'ok'		=> sync_btn_ok)),
				'head'			=> sync_header_backup_continue,
				'is_intro'	=> 0, // Meldung anzeigen
				'intro'			=> $this->getMessage(),
				'job'				=> array(	'name'	=> dbSyncDataJobs::field_id,
															'value'	=> $job_id)
			);
			// Statusmeldung ausgeben
			return $this->getTemplate('backend.backup.message.lte', $data);
		}
	} // backupContinue()
	 
  
  /**
   * Dialog zur Auswahl des Backup Archiv, das fuer einen Restore verwendet
   * werden soll
   * 
   * @return STR dialog
   */
  public function dlgRestore() {
  	global $interface;
  	
  	if (!file_exists($interface->getBackupPath())) {
  		if (!mkdir($interface->getBackupPath(), 0755, true)) {
  			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_mkdir, $interface->getBackupPath())));
  			return false;
  		}
  	}
  	$arcs = $interface->directoryTree($interface->getBackupPath());
  	$archives = array();
  	foreach ($arcs as $arc) {
  		if (is_file($arc) && (pathinfo($arc, PATHINFO_EXTENSION) == 'zip')) $archives[] = $arc;
  	}
  	
  	$select_array = array();
		$select_array[] = array( 
			'value'			=> -1,
			'selected'	=> 1,
			'text'			=> sync_str_restore_select
		);
		foreach ($archives as $archive) {
			$select_array[] = array(
				'value'			=> str_replace(WB_PATH, '', $archive),
				'selected'	=> 0,
				'text'			=> basename($archive)
			);
		}
		
		if (count($archives) < 1) {
			// Mitteilung: kein Archiv gefunden!
			$dir = str_replace(WB_PATH, '', $interface->getBackupPath());
			$this->setMessage(sprintf(sync_msg_no_backup_files_in_dir, $dir, $dir));
		}
		
  	$data = array(
			'form'			=> array(	'name'		=> 'restore_select',
														'link'		=> $this->page_link,
														'action'	=> array( 'name'	=> self::request_action,
																								'value'	=> self::action_restore_info),
														'btn'			=> array(	'ok'		=> sync_btn_ok)
														),
			'restore'		=> array(	'name'		=> self::request_restore,
														'label'		=> sync_label_restore_select,
														'hint'		=> sync_hint_restore_select,
														'options'	=> $select_array),
			'head'			=> sync_header_restore,
			'is_intro'	=> $this->isMessage() ? 0 : 1,
			'intro'			=> $this->isMessage() ? $this->getMessage() : sync_intro_restore
		);
		
		return $this->getTemplate('backend.restore.start.lte', $data);
  } // dlgRestore()
	
  /**
   * Prueft das angegebene Archiv und startet einen Dialog zum Festlegen
   * der Parameter fuer den Restore
   * 
   * @return STR dialog
   */
  public function restoreInfo() {
  	global $dbSyncDataJob;
  	global $kitTools;
  	global $interface;
  	global $dbSyncDataCfg;
  	
  	$backup_archive = (isset($_REQUEST[self::request_restore])) ? $_REQUEST[self::request_restore] : -1;
  	
  	if ($backup_archive == -1) {
  		// kein gueltiges Archiv angegeben, Meldung setzen und zurueck zum Auswahldialog
  		$this->setMessage(sync_msg_no_backup_file_for_process);
  		return $this->dlgRestore();
  	}
  	// get the content of sync_data.ini into the $ini_data array
  	$ini_data = array();
  	if (!$interface->restoreInfo($backup_archive, $ini_data)) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $interface->getError()));
  		return false;
  	}
  	
  	// existiert die Dateiliste im /temp Verzeichnis?
  	if (false === ($list = unserialize(file_get_contents($this->temp_path.syncDataInterface::archive_list)))) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_read, $this->temp_path.syncDataInterface::archive_list)));
  		return false;
  	}
  	
  	// pruefen ob Dateien wiederhergestellt werden solle
  	$restore_info = $interface->array_search($list, 'filename', 'files/', true);
  	$restore_files = (count($restore_info) > 0) ? true : false;
  	$restore_info = $interface->array_search($list, 'filename', 'sql/', true);
  	$restore_tables = (count($restore_info) > 0) ? true : false;
  	
  	// Werte setzen
  	$values = array(
  		array('label'	=> sync_label_archive_id, 		'text' => $ini_data[syncDataInterface::section_general][dbSyncDataJobs::field_archive_id]),
  		array('label'	=> sync_label_archive_number, 'text' => $ini_data[syncDataInterface::section_general][dbSyncDataJobs::field_archive_number]),
  		array('label' => sync_label_archive_type, 	'text' => $dbSyncDataJob->job_type_array[$ini_data[syncDataInterface::section_general][dbSyncDataJobs::field_type]]),
  		array('label' => sync_label_total_files, 		'text' => $ini_data[syncDataInterface::section_general]['total_files']),
  		array('label' => sync_label_total_size, 		'text' => $kitTools->bytes2Str($ini_data[syncDataInterface::section_general]['total_size'])),
  		array('label' => sync_label_wb_url,					'text' => $ini_data[syncDataInterface::section_general]['used_wb_url']),
  		array('label' => sync_label_status, 				'text' => $ini_data[syncDataInterface::section_general][dbSyncDataJobs::field_last_message]),
  		array('label' => sync_label_timestamp, 			'text' => date(sync_cfg_datetime_str, strtotime($ini_data[syncDataInterface::section_general][dbSyncDataJobs::field_timestamp])))
  	);
  	$info = array(
  		'label'		=> sync_label_archive_info,
  		'values'	=> $values
  	);
  	
  	$restore = array(
  		'select'		=> array(	'label'		=> sync_label_restore,
  													'select'	=> array(
  																					array('name'		=> dbSyncDataJobs::field_type,
  																								'value'		=> dbSyncDataJobs::type_restore_mysql,
  																								'text' 		=> sync_label_tables,
  																								'checked'	=> 1,
  																								'enabled'	=> ($restore_tables && ($ini_data[syncDataInterface::section_general][dbSyncDataJobs::field_type] == dbSyncDataJobs::type_backup_complete) || ($ini_data[syncDataInterface::section_general][dbSyncDataJobs::field_type] == dbSyncDataJobs::type_backup_mysql)) ? 1 : 0),
  																					array('name'		=> dbSyncDataJobs::field_type,
  																								'value'		=> dbSyncDataJobs::type_restore_files,
  																								'text' 		=> sync_label_files,
  																								'checked'	=> 1,
  																								'enabled'	=> ($restore_files && ($ini_data[syncDataInterface::section_general][dbSyncDataJobs::field_type] == dbSyncDataJobs::type_backup_complete) || ($ini_data[syncDataInterface::section_general][dbSyncDataJobs::field_type] == dbSyncDataJobs::type_backup_files)) ? 1 : 0))),
  		'mode'		=> array(	'label'		=> sync_label_restore_mode,
  												'select'	=> array(
  																					array('name'		=> dbSyncDataJobs::field_restore_mode,
  																								'value'		=> dbSyncDataJobs::mode_changed_binary,
  																								'text'		=> sync_label_restore_mode_binary,
  																								'checked'	=> 0),
  																					array('name'		=> dbSyncDataJobs::field_restore_mode,
  																								'value'		=> dbSyncDataJobs::mode_changed_date_size,
  																								'text'		=> sync_label_restore_mode_time_size,
  																								'checked'	=> 1),
  																					array('name'		=> dbSyncDataJobs::field_restore_mode,
  																								'value'		=> dbSyncDataJobs::mode_replace_all,
  																								'text'		=> sync_label_restore_mode_replace_all,
  																								'checked'	=> 0))),
  		'replace'		=> array(	'url'			=> array( 'label'		=> sync_label_restore_replace,
										  													'name'		=> dbSyncDataJobs::field_replace_wb_url, //self::request_restore_replace_url,
										  													'value'		=> 1,
										  													'text'		=> sync_label_restore_replace_url,
										  													'checked'	=> 1),
  												 	'prefix'	=> array( 'label'		=> sync_label_restore_replace,
										  													'name'		=> dbSyncDataJobs::field_replace_table_prefix, //self::request_restore_replace_prefix,
										  													'value'		=> 1,
										  													'text'		=> sync_label_restore_replace_prefix,
										  													'checked'	=> 1)),
  		'ignore'		=> array( 'config'	=> array(	'label'		=> sync_label_restore_ignore,
  																							'name'		=> dbSyncDataJobs::field_ignore_config,
  																							'value'		=> 1,
  																							'text'		=> sync_label_restore_ignore_config,
  																							'checked'	=> 1),
  													'htaccess'	=> array('label'	=> sync_label_restore_ignore,
  																							'name'		=> dbSyncDataJobs::field_ignore_htaccess,
  																							'value'		=> 1,
  																							'text'		=> sync_label_restore_ignore_htaccess,
  																							'checked'	=> 1)),
  		'delete'		=> array(	'tables'	=> array(	'label'		=> sync_label_restore_delete,
  																							'name'		=> dbSyncDataJobs::field_delete_tables,
  																							'value'		=> 1,
  																							'text'		=> sync_label_restore_delete_tables,
  																							'checked'	=> 0,
  																							'enabled'	=> 1),
  													'files'	=> array(		'label'		=> sync_label_restore_delete,
  																							'name'		=> dbSyncDataJobs::field_delete_files,
  																							'value'		=> 1,
  																							'text'		=> sync_label_restore_delete_files,
  																							'checked'	=> 0,
  																							'enabled'	=> 1))																	
  	);
  	
  	$data = array(
			'form'			=> array(	'name'		=> 'restore_info',
														'link'		=> $this->page_link,
														'action'	=> array( 'name'	=> self::request_action,
																								'value'	=> self::action_restore_start),
  													'restore'	=> array(	'name' 	=> self::request_restore,
  																							'value'	=> $backup_archive),
														'btn'			=> array(	'ok'		=> sync_btn_start,
  																							'abort'	=> sync_btn_abort)
														),
			'info'			=> $info,
			'restore'		=> $restore,
			'head'			=> sync_header_restore,
			'is_intro'	=> $this->isMessage() ? 0 : 1,
			'intro'			=> $this->isMessage() ? $this->getMessage() : sync_intro_restore_info,
			'text_process' 	=> sprintf(sync_msg_restore_running, $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgLimitExecutionTime)),
			'img_url'		=> $this->img_url
		);
		
		return $this->getTemplate('backend.restore.archive.info.lte', $data);
  } // restoreInfo()
  
  /**
   * Startet die Datenwiederherstellung
   */
  public function restoreStart()  {
		global $interface;
		
  	$backup_archive = (isset($_REQUEST[self::request_restore])) ? $_REQUEST[self::request_restore] : -1;
  	
  	// Backup Archiv angegeben?
  	if ($backup_archive == -1) {
  		// kein gueltiges Archiv angegeben, Meldung setzen und zurueck zum Auswahldialog
  		$this-set_error(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sync_error_backup_archive_invalid));
  		return false;
  	}
  	
  	// gettting the params for restoring
  	$replace_prefix 			= isset($_REQUEST[dbSyncDataJobs::field_replace_table_prefix]) ? true : false;
  	$replace_url					= isset($_REQUEST[dbSyncDataJobs::field_replace_wb_url]) ? true : false;
  	$restore_mode					= isset($_REQUEST[dbSyncDataJobs::field_restore_mode]) ? $_REQUEST[dbSyncDataJobs::field_restore_mode] : dbSyncDataJobs::mode_changed_date_size;
  	$restore_type					= $_REQUEST[dbSyncDataJobs::field_type];
  	$ignore_config				= isset($_REQUEST[dbSyncDataJobs::field_ignore_config]) ? true : false;
  	$ignore_htaccess			= isset($_REQUEST[dbSyncDataJobs::field_ignore_htaccess]) ? true : false;
  	$delete_files					= isset($_REQUEST[dbSyncDataJobs::field_delete_files]) ? true : false;
  	$delete_tables				= isset($_REQUEST[dbSyncDataJobs::field_delete_tables]) ? true : false;
  	
  	$job_id = -1;
  	$status = $interface->restoreStart($backup_archive, $replace_prefix, $replace_url, $restore_type, $restore_mode, $ignore_config, $ignore_htaccess, $delete_files, $delete_tables, $job_id); 
  	if ($interface->isError()) {
  		// error executing interface
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $interface->getError()));
  		return false;
  	}
  	
  	if ($status == dbSyncDataJobs::status_time_out) {
  		// interrupt restore
			return $this->messageRestoreInterrupt($job_id);
		}
		elseif ($status == dbSyncDataJobs::status_finished) {
			// finish restore
			return $this->messageRestoreFinished($job_id);
		}
		else {
			// unknown status ...
			$this->setError(sprintf('[%s %s] %s', __METHOD__, __LINE__, sync_error_status_unknown));
			return false;
		}
  } // restoreStart()
  
  public function restoreContinue() {
  	global $interface;
  	
  	$job_id = isset($_REQUEST[dbSyncDataJobs::field_id]) ? $_REQUEST[dbSyncDataJobs::field_id] : -1;
  	
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
			return $this->messageRestoreInterrupt($job_id);
		}
		elseif ($status == dbSyncDataJobs::status_finished) {
			// finish restore
			return $this->messageRestoreFinished($job_id);
		}
		else {
			// unknown status ...
			$this->setError(sprintf('[%s %s] %s', __METHOD__, __LINE__, sync_error_status_unknown));
			return false;
		}
  } // restoreContinue()
  
  /**
   * Prompt message: restoring process is interrupted
   * 
   * @param INT $job_id
   * @return STR message dialog
   */
  public function messageRestoreInterrupt($job_id) {
		global $dbSyncDataJob;
		global $kitTools;
		global $dbSyncDataProtocol;
		global $dbSyncDataCfg;
		
		$where = array(dbSyncDataJobs::field_id => $job_id);
		$job = array();
		if (!$dbSyncDataJob->sqlSelectRecord($where, $job)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		$job = $job[0];
		
		// walk through tables and files which are added, deleted or replaced
		$check_array = array(	dbSyncDataProtocol::action_mysql_add,
													dbSyncDataProtocol::action_mysql_delete,
													dbSyncDataProtocol::action_mysql_replace,
													dbSyncDataProtocol::action_file_add,
													dbSyncDataProtocol::action_file_delete,
													dbSyncDataProtocol::action_file_replace);
		$result_array = array();
		foreach ($check_array as $action) {
			$SQL = sprintf( "SELECT COUNT(%s) AS count, SUM(%s) AS bytes FROM %s WHERE %s='%s' AND %s='%s' AND %s='%s'",
											dbSyncDataProtocol::field_file,
											dbSyncDataProtocol::field_size,
											$dbSyncDataProtocol->getTableName(),
											dbSyncDataProtocol::field_job_id,
											$job_id,
											dbSyncDataProtocol::field_action,
											$action,
											dbSyncDataProtocol::field_status,
											dbSyncDataProtocol::status_ok);
			$result = array();
			if (!$dbSyncDataProtocol->sqlExec($SQL, $result)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataProtocol->getError()));
				return false;
			}
			$result_array[$action]['count'] = isset($result[0]['bytes']) ? $result[0]['count'] : 0;
			$result_array[$action]['bytes'] = isset($result[0]['bytes']) ? $result[0]['bytes'] : 0;				
		}
		
		$auto_exec_msec = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgAutoExecMSec);
		$auto_exec = $auto_exec_msec > 0 ? sprintf(sync_msg_auto_exec_msec, $auto_exec_msec) : '';
		
		$info = sprintf(sync_msg_restore_interrupted,
										$this->max_execution_time,
										$result_array[dbSyncDataProtocol::action_mysql_delete]['count'],
										$kitTools->bytes2Str($result_array[dbSyncDataProtocol::action_mysql_delete]['bytes']),
										$result_array[dbSyncDataProtocol::action_mysql_add]['count'],
										$kitTools->bytes2Str($result_array[dbSyncDataProtocol::action_mysql_add]['bytes']),
										$result_array[dbSyncDataProtocol::action_mysql_replace]['count'],
										$kitTools->bytes2Str($result_array[dbSyncDataProtocol::action_mysql_replace]['bytes']),
										$result_array[dbSyncDataProtocol::action_file_delete]['count'],
										$kitTools->bytes2Str($result_array[dbSyncDataProtocol::action_file_delete]['bytes']),
										$result_array[dbSyncDataProtocol::action_file_add]['count'],
										$kitTools->bytes2Str($result_array[dbSyncDataProtocol::action_file_add]['bytes']),
										$result_array[dbSyncDataProtocol::action_file_replace]['count'],
										$kitTools->bytes2Str($result_array[dbSyncDataProtocol::action_file_replace]['bytes']),
										$auto_exec										
										);
		
		$data = array(
			'form'			=> array(	'name'		=> 'restore_continue',
														'link'		=> $this->page_link,
														'action'	=> array(	'name'	=> self::request_action,
																								'value'	=> self::action_restore_continue),
														'btn'			=> array(	'abort'	=> sync_btn_abort,
																								'ok'		=> sync_btn_continue)),
			'head'			=> sync_header_restore_continue,
			'is_intro'	=> $this->isMessage() ? 0 : 1,
			'intro'			=> $this->isMessage() ? $this->getMessage() : $info,
			'job'				=> array(	'name'	=> dbSyncDataJobs::field_id,
														'value'	=> $job_id),
			'text_process' 	=> sprintf(sync_msg_restore_running, $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgLimitExecutionTime)),
			'img_url'		=> $this->img_url,
			'auto_exec_msec' => $auto_exec_msec
		);
		// Statusmeldung ausgeben
		return $this->getTemplate('backend.restore.interrupt.lte', $data);
	} // messageRestoreInterrupt()
	
	/**
	 * Prompt message: restoring process is finished
	 * 
	 * @param INT $job_id
	 * @return STR message dialog
	 */
  public function messageRestoreFinished($job_id) {
  	global $dbSyncDataJob;
		global $kitTools;
		global $dbSyncDataProtocol;
		
		$where = array(dbSyncDataJobs::field_id => $job_id);
		$job = array();
		if (!$dbSyncDataJob->sqlSelectRecord($where, $job)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		$job = $job[0];
		
		// walk through tables and files which are added, deleted or replaced
		$check_array = array(	dbSyncDataProtocol::action_mysql_add,
													dbSyncDataProtocol::action_mysql_delete,
													dbSyncDataProtocol::action_mysql_replace,
													dbSyncDataProtocol::action_file_add,
													dbSyncDataProtocol::action_file_delete,
													dbSyncDataProtocol::action_file_replace);
		$result_array = array();
		foreach ($check_array as $action) {
			$SQL = sprintf( "SELECT COUNT(%s) AS count, SUM(%s) AS bytes FROM %s WHERE %s='%s' AND %s='%s' AND %s='%s'",
											dbSyncDataProtocol::field_file,
											dbSyncDataProtocol::field_size,
											$dbSyncDataProtocol->getTableName(),
											dbSyncDataProtocol::field_job_id,
											$job_id,
											dbSyncDataProtocol::field_action,
											$action,
											dbSyncDataProtocol::field_status,
											dbSyncDataProtocol::status_ok);
			$result = array();
			if (!$dbSyncDataProtocol->sqlExec($SQL, $result)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataProtocol->getError()));
				return false;
			}
			$result_array[$action]['count'] = isset($result[0]['bytes']) ? $result[0]['count'] : 0;
			$result_array[$action]['bytes'] = isset($result[0]['bytes']) ? $result[0]['bytes'] : 0;				
		}
		
		$info = sprintf(sync_msg_restore_finished,
										$result_array[dbSyncDataProtocol::action_mysql_delete]['count'],
										$kitTools->bytes2Str($result_array[dbSyncDataProtocol::action_mysql_delete]['bytes']),
										$result_array[dbSyncDataProtocol::action_mysql_add]['count'],
										$kitTools->bytes2Str($result_array[dbSyncDataProtocol::action_mysql_add]['bytes']),
										$result_array[dbSyncDataProtocol::action_mysql_replace]['count'],
										$kitTools->bytes2Str($result_array[dbSyncDataProtocol::action_mysql_replace]['bytes']),
										$result_array[dbSyncDataProtocol::action_file_delete]['count'],
										$kitTools->bytes2Str($result_array[dbSyncDataProtocol::action_file_delete]['bytes']),
										$result_array[dbSyncDataProtocol::action_file_add]['count'],
										$kitTools->bytes2Str($result_array[dbSyncDataProtocol::action_file_add]['bytes']),
										$result_array[dbSyncDataProtocol::action_file_replace]['count'],
										$kitTools->bytes2Str($result_array[dbSyncDataProtocol::action_file_replace]['bytes'])										
										);
										
		$data = array(
			'form'			=> array(	'name'		=> 'restore_finished',
														'link'		=> $this->page_link,
														'action'	=> array(	'name'	=> self::request_action,
																								'value'	=> self::action_default),
														'btn'			=> array(	'ok'		=> sync_btn_ok)),
			'head'			=> sync_header_restore_finished,
			'is_intro'	=> $this->isMessage() ? 0 : 1,
			'intro'			=> $this->isMessage() ? $this->getMessage() : $info,
			'job'				=> array(	'name'	=> dbSyncDataJobs::field_id,
														'value'	=> $job_id),
		);
		// Statusmeldung ausgeben
		return $this->getTemplate('backend.restore.message.lte', $data);  	
	} // messageRestoreFinished()
	
	/**
	 * Start the process of updating an existing backup.
	 * Gather the informations and call the interface for processing
	 * 
	 * @return STR|BOOL dialog or FALSE on error
	 */
	public function updateStart() {
		global $interface;
		
		$archive_id = isset($_REQUEST[dbSyncDataArchives::field_archive_id]) ? $_REQUEST[dbSyncDataArchives::field_archive_id] : -1;
		$update_name = isset($_REQUEST[dbSyncDataArchives::field_archive_name]) ? $_REQUEST[dbSyncDataArchives::field_archive_name] : '';
		
		$job_id = -1;
		$status = $interface->updateStart($archive_id, $update_name, $job_id);
		if ($interface->isError()) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $interface->getError()));
			return false; 
		}
		
		if ($status == dbSyncDataJobs::status_time_out) {
			return $this->messageUpdateInterrupt($job_id);
		}
		elseif ($status == dbSyncDataJobs::status_finished) {
			return $this->messageUpdateFinished($job_id);
		}
		else {
			// unknown status ...
			$this->setError(sprintf('[%s %s] %s', __METHOD__, __LINE__, sync_error_status_unknown));
			return false;
		}
	} // updateStart()
	
	/**
	 * Continue the update Process after an interrupt
	 * 
	 * @return STR|BOOL dialog or FALSE on error
	 */
	public function updateContinue() {
		global $interface;
		
		$job_id = isset($_REQUEST[dbSyncDataJobs::field_id]) ? $_REQUEST[dbSyncDataJobs::field_id] : -1;
		
		if ($job_id < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_job_id_invalid, $job_id)));
			return false;
		}
		
		$status = $interface->updateContinue($job_id);
		if ($interface->isError()) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $interface->getError()));
			return false;
		}
		if ($status == dbSyncDataJobs::status_time_out) {
			return $this->messageUpdateInterrupt($job_id);
		}
		elseif ($status == dbSyncDataJobs::status_finished) {
			return $this->messageUpdateFinished($job_id);
		}
		else {
			// in allen anderen Faellen ist nichts zu tun
			$this->setMessage(sync_msg_nothing_to_do);
			$data = array(
				'form'			=> array(	'name'		=> 'update_stop',
															'link'		=> $this->page_link,
															'action'	=> array(	'name'	=> self::request_action,
																									'value'	=> self::action_default),
															'btn'			=> array(	'abort'	=> sync_btn_abort,
																									'ok'		=> sync_btn_ok)),
				'head'			=> sync_header_update_continue,
				'is_intro'	=> 0, // Meldung anzeigen
				'intro'			=> $this->getMessage(),
				'job'				=> array(	'name'	=> dbSyncDataJobs::field_id,
															'value'	=> $job_id)
			);
			// Statusmeldung ausgeben
			return $this->getTemplate('backend.backup.message.lte', $data);
		}
	} // updateContinue()
	
	/**
	 * Return a message that the update process is interrupted.
	 * Shows some statistics and additional informations.
	 * 
	 * @param INT $job_id
	 * @return STR message dialog
	 */
	public function messageUpdateInterrupt($job_id) {
		global $dbSyncDataJob;
		global $dbSyncDataFile;
		global $kitTools;
		global $dbSyncDataCfg;
		
		$where = array(dbSyncDataJobs::field_id => $job_id);
		$job = array();
		if (!$dbSyncDataJob->sqlSelectRecord($where, $job)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		$job = $job[0];
		
		// Anzahl und Umfang der bisher gesicherten Dateien ermitteln
		$SQL = sprintf(	"SELECT COUNT(%s) AS count, SUM(%s) AS bytes FROM %s WHERE %s='%s' AND %s='%s' AND %s='%s' AND (%s='%s' OR %s='%s')",
										dbSyncDataFiles::field_file_name,
										dbSyncDataFiles::field_file_size,
										$dbSyncDataFile->getTableName(),
										dbSyncDataFiles::field_archive_id,
										$job[dbSyncDataJobs::field_archive_id],
										dbSyncDataFiles::field_archive_number,
										$job[dbSyncDataJobs::field_archive_number],
										dbSyncDataFiles::field_status,
										dbSyncDataFiles::status_ok,
										dbSyncDataFiles::field_action,
										dbSyncDataFiles::action_add,
										dbSyncDataFiles::field_action,
										dbSyncDataFiles::action_replace);
		$files = array();
		if (!$dbSyncDataFile->sqlExec($SQL, $files)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
			return false;
		}
		
		$auto_exec_msec = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgAutoExecMSec);
		$auto_exec = $auto_exec_msec > 0 ? sprintf(sync_msg_auto_exec_msec, $auto_exec_msec) : '';
		
		$info = sprintf(	sync_msg_update_to_be_continued,
											$this->max_execution_time,
											$files[0]['count'],
											$kitTools->bytes2Str($files[0]['bytes']),
											$auto_exec );
		$data = array(
			'form'			=> array(	'name'		=> 'update_continue',
														'link'		=> $this->page_link,
														'action'	=> array(	'name'	=> self::request_action,
																								'value'	=> self::action_update_continue),
														'btn'			=> array(	'abort'	=> sync_btn_abort,
																								'ok'		=> sync_btn_continue)),
			'head'			=> sync_header_update_continue,
			'is_intro'	=> $this->isMessage() ? 0 : 1,
			'intro'			=> $this->isMessage() ? $this->getMessage() : $info,
			'job'				=> array(	'name'	=> dbSyncDataJobs::field_id,
														'value'	=> $job_id),
			'text_process' 	=> sprintf(sync_msg_update_running, $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgLimitExecutionTime)),
			'img_url'		=> $this->img_url,
			'auto_exec_msec' => $auto_exec_msec
		);
		// Statusmeldung ausgeben
		return $this->getTemplate('backend.backup.interrupt.lte', $data);
	} // messageUpdateInterrupt()
	
	/**
	 * Return a message that the update process is finished.
	 * Shows some statistics and additional informations.
	 * 
	 * @param INT $job_id
	 * @return STR message dialog
	 */
	public function messageUpdateFinished($job_id) {
		global $dbSyncDataJob;
		global $dbSyncDataFile;
		global $kitTools;
		global $interface;
		global $dbSyncDataArchive;
		
		$where = array(dbSyncDataJobs::field_id => $job_id);
		$job = array();
		if (!$dbSyncDataJob->sqlSelectRecord($where, $job)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		if (count($job) < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_job_id_invalid, $job_id)));
			return false;
		}
		$job = $job[0];
		
		$where = array(	dbSyncDataArchives::field_archive_id 			=> $job[dbSyncDataJobs::field_archive_id],
										dbSyncDataArchives::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number]);
		$archive = array();
		if (!$dbSyncDataArchive->sqlSelectRecord($where, $archive)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataArchive->getError()));
			return false;
		}
		if (count($archive) < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_archive_id_invalid, $job[dbSyncDataJobs::field_archive_id])));
			return false;
		}
		$archive = $archive[0];
		
		// Anzahl und Umfang der bisher gesicherten Dateien ermitteln
		$SQL = sprintf(	"SELECT COUNT(%s) AS count, SUM(%s) AS bytes FROM %s WHERE %s='%s' AND %s='%s' AND %s='%s' AND (%s='%s' OR %s='%s')",
										dbSyncDataFiles::field_file_name,
										dbSyncDataFiles::field_file_size,
										$dbSyncDataFile->getTableName(),
										dbSyncDataFiles::field_archive_id,
										$job[dbSyncDataJobs::field_archive_id],
										dbSyncDataFiles::field_archive_number,
										$job[dbSyncDataJobs::field_archive_number],
										dbSyncDataFiles::field_status,
										dbSyncDataFiles::status_ok,
										dbSyncDataFiles::field_action,
										dbSyncDataFiles::action_add,
										dbSyncDataFiles::field_action,
										dbSyncDataFiles::action_replace);
		$files = array();
		if (!$dbSyncDataFile->sqlExec($SQL, $files)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
			return false;
		}
		
		// Meldung zusammenstellen
		$info = sprintf(	sync_msg_update_finished,
											$files[0]['count'],
											$kitTools->bytes2Str($files[0]['bytes']),
											str_replace(WB_PATH, WB_URL, $interface->getBackupPath().$archive[dbSyncDataArchives::field_archive_name]),
											str_replace(WB_PATH, WB_URL, $interface->getBackupPath().$archive[dbSyncDataArchives::field_archive_name]) );
		$data = array(
			'form'			=> array(	'name'		=> 'update_finished',
														'link'		=> $this->page_link,
														'action'	=> array(	'name'	=> self::request_action,
																								'value'	=> self::action_default),
														'btn'			=> array(	'ok'		=> sync_btn_ok)),
			'head'			=> sync_header_update_finished,
			'is_intro'	=> $this->isMessage() ? 0 : 1,
			'intro'			=> $this->isMessage() ? $this->getMessage() : $info,
			'job'				=> array(	'name'	=> dbSyncDataJobs::field_id,
														'value'	=> $job_id)
		);
		// Statusmeldung ausgeben
		return $this->getTemplate('backend.backup.message.lte', $data);	
	} // messageUpdateFinished()
	
} // class syncBackend

?>