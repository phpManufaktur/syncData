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

class dbSyncDataCfg extends dbConnectLE {
	
	const field_id						= 'cfg_id';
	const field_name					= 'cfg_name';
	const field_type					= 'cfg_type';
	const field_value					= 'cfg_value';
	const field_label					= 'cfg_label';
	const field_description		= 'cfg_desc';
	const field_status				= 'cfg_status';
	const field_update_by			= 'cfg_update_by';
	const field_update_when		= 'cfg_update_when';
	
	const status_active				= 1;
	const status_deleted			= 0;
	
	const type_undefined			= 0;
	const type_array					= 7;
  const type_boolean				= 1;
  const type_email					= 2;
  const type_float					= 3;
  const type_integer				= 4;
  const type_list						= 9;
  const type_path						= 5;
  const type_string					= 6;
  const type_url						= 8;
  
  public $type_array = array(
  	self::type_undefined		=> '-UNDEFINED-',
  	self::type_array				=> 'ARRAY',
  	self::type_boolean			=> 'BOOLEAN',
  	self::type_email				=> 'E-MAIL',
  	self::type_float				=> 'FLOAT',
  	self::type_integer			=> 'INTEGER',
  	self::type_list					=> 'LIST',
  	self::type_path					=> 'PATH',
  	self::type_string				=> 'STRING',
  	self::type_url					=> 'URL'
  );
  
  private $createTables 		= false;
  private $message					= '';

  const cfgIgnoreDirectories		= 'cfgIgnoreDirectories';	
  const cfgMaxExecutionTime			= 'cfgMaxExecutionTime';
  const cfgLimitExecutionTime		=	'cfgLimitExecutionTime'; 
  const cfgMemoryLimit					= 'cfgMemoryLimit';
  const cfgIgnoreTables					= 'cfgIgnoreTables';
  const cfgIgnoreFileExtensions	= 'cfgIgnoreFileExtensions';
  const cfgFileMTimeDiffAllowed	= 'cfgFileMTimeDiffAllowed';
  const cfgAutoExecMSec					= 'cfgAutoExecMSec';
  const cfgServerActive					= 'cfgServerActive';
  const cfgServerArchiveID			= 'cfgServerArchiveID';
  
  public $config_array = array(
  	array('sync_label_cfg_max_execution_time', self::cfgMaxExecutionTime, self::type_integer, '30', 'sync_desc_cfg_max_execution_time'),
  	array('sync_label_cfg_ignore_directories', self::cfgIgnoreDirectories, self::type_list, '/temp/,/modules/dwoo/dwoo-1.1.1/dwoo/cache/,/modules/dwoo/dwoo-1.1.1/dwoo/compiled/,/modules/sync_data/,/media/sync_data/', 'sync_desc_cfg_ignore_directories'),
  	array('sync_label_cfg_memory_limit', self::cfgMemoryLimit, self::type_string, '256M', 'sync_desc_cfg_memory_limit'),
  	array('sync_label_cfg_limit_execution_time', self::cfgLimitExecutionTime, self::type_integer, '25', 'sync_desc_cfg_limit_execution_time'),
  	array('sync_label_cfg_ignore_tables', self::cfgIgnoreTables, self::type_list, 'mod_sync_data_archives,mod_sync_data_config,mod_sync_data_cronjob_data,mod_sync_data_files,mod_sync_data_jobs,mod_sync_data_protocol,users', 'sync_desc_cfg_ignore_tables'),
  	array('sync_label_cfg_ignore_file_extensions', self::cfgIgnoreFileExtensions, self::type_array, 'buildpath,project,tmp', 'sync_desc_cfg_ignore_file_extensions'),
  	array('sync_label_cfg_filemtime_diff_allowed', self::cfgFileMTimeDiffAllowed, self::type_integer, '1', 'sync_desc_cfg_filemtime_diff_allowed'),
  	array('sync_label_cfg_auto_exec_msec', self::cfgAutoExecMSec, self::type_integer, '5000', 'sync_desc_cfg_auto_exec_msec'),
  	array('sync_label_cfg_server_active', self::cfgServerActive, self::type_boolean, '0', 'sync_desc_cfg_server_active'), 
  	array('sync_label_cfg_server_archive_id', self::cfgServerArchiveID, self::type_string, '', 'sync_desc_cfg_server_archive_id') 
  );  
  
  public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_sync_data_config');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
  	$this->addFieldDefinition(self::field_name, "VARCHAR(32) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_type, "TINYINT UNSIGNED NOT NULL DEFAULT '".self::type_undefined."'");
  	$this->addFieldDefinition(self::field_value, "TEXT NOT NULL DEFAULT ''", false, false, true);
  	$this->addFieldDefinition(self::field_label, "VARCHAR(64) NOT NULL DEFAULT 'sync_str_undefined'");
  	$this->addFieldDefinition(self::field_description, "VARCHAR(255) NOT NULL DEFAULT 'sync_str_undefined'");
  	$this->addFieldDefinition(self::field_status, "TINYINT UNSIGNED NOT NULL DEFAULT '".self::status_active."'");
  	$this->addFieldDefinition(self::field_update_by, "VARCHAR(32) NOT NULL DEFAULT 'SYSTEM'");
  	$this->addFieldDefinition(self::field_update_when, "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
  	$this->setIndexFields(array(self::field_name));
  	$this->setAllowedHTMLtags('<a><abbr><acronym><span>');
  	$this->checkFieldDefinitions();
  	// Tabelle erstellen
  	if ($this->createTables) {
  		if (!$this->sqlTableExists()) {
  			if (!$this->sqlCreateTable()) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  			}
  		}
  	}
  	// Default Werte garantieren
  	if ($this->sqlTableExists()) {
  		$this->checkConfig();
  	}
  	date_default_timezone_set(sync_cfg_time_zone);
  } // __construct()
  
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
   * Aktualisiert den Wert $new_value des Datensatz $name
   * 
   * @param $new_value STR - Wert, der uebernommen werden soll
   * @param $id INT - ID des Datensatz, dessen Wert aktualisiert werden soll
   * 
   * @return BOOL Ergebnis
   * 
   */
  public function setValueByName($new_value, $name) {
  	$where = array();
  	$where[self::field_name] = $name;
  	$config = array();
  	if (!$this->sqlSelectRecord($where, $config)) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  		return false;
  	}
  	if (sizeof($config) < 1) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_cfg_name, $name)));
  		return false;
  	}
  	return $this->setValue($new_value, $config[0][self::field_id]);
  } // setValueByName()
  
  /**
   * Haengt einen Slash an das Ende des uebergebenen Strings
   * wenn das letzte Zeichen noch kein Slash ist
   *
   * @param STR $path
   * @return STR
   */
  public function addSlash($path) {
  	$path = substr($path, strlen($path)-1, 1) == "/" ? $path : $path."/";
  	return $path;  
  }
  
  /**
   * Wandelt einen String in einen Float Wert um.
   * Geht davon aus, dass Dezimalzahlen mit ',' und nicht mit '.'
   * eingegeben wurden.
   *
   * @param STR $string
   * @return FLOAT
   */
  public function str2float($string) {
  	$string = str_replace('.', '', $string);
		$string = str_replace(',', '.', $string);
		$float = floatval($string);
		return $float;
  }

  public function str2int($string) {
  	$string = str_replace('.', '', $string);
		$string = str_replace(',', '.', $string);
		$int = intval($string);
		return $int;
  }
  
	/**
	 * Ueberprueft die uebergebene E-Mail Adresse auf logische Gueltigkeit
	 *
	 * @param STR $email
	 * @return BOOL
	 */
	public function validateEMail($email) {
		//if(eregi("^([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$", $email)) {
		// PHP 5.3 compatibility - eregi is deprecated
		if(preg_match("/^([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$/i", $email)) {
			return true; }
		else {
			return false; }
	}
  
  /**
   * Aktualisiert den Wert $new_value des Datensatz $id
   * 
   * @param $new_value STR - Wert, der uebernommen werden soll
   * @param $id INT - ID des Datensatz, dessen Wert aktualisiert werden soll
   * 
   * @return BOOL Ergebnis
   */
  public function setValue($new_value, $id) {
  	$value = '';
  	$where = array();
  	$where[self::field_id] = $id;
  	$config = array();
  	if (!$this->sqlSelectRecord($where, $config)) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  		return false;
  	}
  	if (sizeof($config) < 1) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_cfg_id, $id)));
  		return false;
  	}
  	$config = $config[0];
  	switch ($config[self::field_type]):
  	case self::type_array:
  		// Funktion geht davon aus, dass $value als STR uebergeben wird!!!
  		$worker = explode(",", $new_value);
  		$data = array();
  		foreach ($worker as $item) {
  			$data[] = trim($item);
  		};
  		$value = implode(",", $data);  			
  		break;
  	case self::type_boolean:
  		$value = (bool) $new_value;
  		$value = (int) $value;
  		break;
  	case self::type_email:
  		if ($this->validateEMail($new_value)) {
  			$value = trim($new_value);
  		}
  		else {
  			$this->setMessage(sprintf(sync_msg_invalid_email, $new_value));
  			return false;			
  		}
  		break;
  	case self::type_float:
  		$value = $this->str2float($new_value);
  		break;
  	case self::type_integer:
  		$value = $this->str2int($new_value);
  		break;
  	case self::type_url:
  	case self::type_path:
  		$value = $this->addSlash(trim($new_value));
  		break;
  	case self::type_string:
  		$value = (string) trim($new_value);
  		// Hochkommas demaskieren
  		$value = str_replace('&quot;', '"', $value);
  		break;
  	case self::type_list:
  		$lines = nl2br($new_value); 
  		$lines = explode('<br />', $lines);
  		$val = array();
  		foreach ($lines as $line) {
  			$line = trim($line);
  			if (!empty($line)) $val[] = $line;
  		}
  		$value = implode(",", $val);
  		break;
  	endswitch;
  	unset($config[self::field_id]);
  	$config[self::field_value] = (string) $value;
  	$config[self::field_update_by] = 'SYSTEM';
  	$config[self::field_update_when] = date('Y-m-d H:i:s');
  	if (!$this->sqlUpdateRecord($config, $where)) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  		return false;
  	}
  	return true;
  } // setValue()
  
  /**
   * Gibt den angeforderten Wert zurueck
   * 
   * @param $name - Bezeichner 
   * 
   * @return WERT entsprechend des TYP
   */
  public function getValue($name) {
  	$result = '';
  	$where = array();
  	$where[self::field_name] = $name;
  	$config = array();
  	if (!$this->sqlSelectRecord($where, $config)) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  		return false;
  	}
  	if (sizeof($config) < 1) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_cfg_name, $name)));
  		return false;
  	}
  	$config = $config[0];
  	switch ($config[self::field_type]):
  	case self::type_array:
  		$result = explode(",", $config[self::field_value]);
  		break;
  	case self::type_boolean:
  		$result = (bool) $config[self::field_value];
  		break;
  	case self::type_email:
  	case self::type_path:
  	case self::type_string:
  	case self::type_url:
  		$result = (string) utf8_decode($config[self::field_value]);
  		break;
  	case self::type_float:
  		$result = (float) $config[self::field_value];
  		break;
  	case self::type_integer:
  		$result = (integer) $config[self::field_value];
  		break;
  	case self::type_list:
  		$result = str_replace(",", "\n", $config[self::field_value]);
  		break;
  	default:
  		echo $config[self::field_value];
  		$result = utf8_decode($config[self::field_value]);
  		break;
  	endswitch;
  	return $result;
  } // getValue()
  
  public function checkConfig() {
  	foreach ($this->config_array as $item) {
  		$where = array();
  		$where[self::field_name] = $item[1];
  		$check = array();
  		if (!$this->sqlSelectRecord($where, $check)) {
  			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  			return false;
  		}
  		if (sizeof($check) < 1) {
  			// Eintrag existiert nicht
  			$data = array();
  			$data[self::field_label] = $item[0];
  			$data[self::field_name] = $item[1];
  			$data[self::field_type] = $item[2];
  			$data[self::field_value] = $item[3];
  			$data[self::field_description] = $item[4];
  			$data[self::field_update_when] = date('Y-m-d H:i:s');
  			$data[self::field_update_by] = 'SYSTEM';
  			if (!$this->sqlInsertRecord($data)) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  				return false;
  			}
  		}
  	}
  	return true;
  }
	  
} // class dbSyncDataCfg

class dbSyncDataJobs extends dbConnectLE {
	
	const field_id										= 'job_id';
	const field_start									= 'job_start';
	const field_end										= 'job_end';
	const field_total_time						= 'job_total_time'; // Sekunden, FLOAT
	const field_type									= 'job_type';
	const field_status								= 'job_status';
	const field_errors								= 'job_errors';
	const field_last_error						= 'job_last_error';
	const field_last_message					= 'job_last_message';
	const field_next_action						= 'job_next_action';
	const field_next_file							= 'job_next_file';
	const field_archive_id						= 'job_archive_id';
	const field_archive_number				= 'job_archive_number';
	const field_archive_file					= 'job_archive_file';
	const field_restore_mode					= 'job_restore_mode';
	const field_replace_wb_url				= 'job_replace_wb_url';
	const field_replace_table_prefix	= 'job_replace_table_prefix';
	const field_ignore_htaccess				= 'job_ignore_htaccess';
	const field_ignore_config					= 'job_ignore_config';
	const field_delete_tables					= 'job_delete_tables';
	const field_delete_files					= 'job_delete_files';
	const field_timestamp							= 'job_timestamp';
	
	const type_undefined				= 1;
	const type_backup_complete	= 2;
	const type_backup_mysql			= 4;
	const type_backup_files			= 8;
	const type_restore_complete	= 16;
	const type_restore_mysql		= 32;
	const type_restore_files		= 64;
	
	const mode_replace_all				= 1;
	const mode_changed_date_size	= 2;
	const mode_changed_binary			= 4;
	
	public $job_type_array = array(
		self::type_backup_complete => sync_type_complete,
		self::type_backup_mysql => sync_type_mysql,
		self::type_backup_files => sync_type_files,
	);
	
	
	const status_undefined		= 1;
	const status_start				= 2;
	const status_running			= 4;
	const status_aborted			= 8;
	const status_finished			= 16;
	const status_time_out			= 32;
	
	const next_action_none		= 1;
	const next_action_mysql		= 2;
	const next_action_file		= 4;
	
	private $createTables 		= false;
	
	public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_sync_data_jobs');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
  	$this->addFieldDefinition(self::field_start, "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
  	$this->addFieldDefinition(self::field_end, "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
  	$this->addFieldDefinition(self::field_total_time, "FLOAT NOT NULL DEFAULT '0'");
  	$this->addFieldDefinition(self::field_type, "INT(11) NOT NULL DEFAULT '".self::type_undefined."'");
  	$this->addFieldDefinition(self::field_status, "INT(11) NOT NULL DEFAULT '".self::status_undefined."'");
  	$this->addFieldDefinition(self::field_errors, "INT(11) NOT NULL DEFAULT '0'");
  	$this->addFieldDefinition(self::field_last_error, "TEXT NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_last_message, "TEXT NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_archive_id, "VARCHAR(40) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_archive_number, "INT(11) NOT NULL DEFAULT '1'");
  	$this->addFieldDefinition(self::field_next_action, "TINYINT NOT NULL DEFAULT '".self::next_action_none."'");
  	$this->addFieldDefinition(self::field_next_file, "TEXT NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_archive_file, "TEXT NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_restore_mode, "TINYINT NOT NULL DEFAULT '".self::mode_changed_date_size."'");
  	$this->addFieldDefinition(self::field_replace_table_prefix, "TINYINT NOT NULL DEFAULT '1'");
  	$this->addFieldDefinition(self::field_replace_wb_url, "TINYINT NOT NULL DEFAULT '1'");
  	$this->addFieldDefinition(self::field_ignore_config, "TINYINT NOT NULL DEFAULT '1'");
  	$this->addFieldDefinition(self::field_ignore_htaccess, "TINYINT NOT NULL DEFAULT '1'");
  	$this->addFieldDefinition(self::field_delete_files, "TINYINT NOT NULL DEFAULT '0'");
  	$this->addFieldDefinition(self::field_delete_tables, "TINYINT NOT NULL DEFAULT '0'");
  	$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP"); 
  	$this->setIndexFields(array(self::field_type, self::field_status, self::field_archive_id));
  	$this->checkFieldDefinitions();
  	// Tabelle erstellen
  	if ($this->createTables) {
  		if (!$this->sqlTableExists()) {
  			if (!$this->sqlCreateTable()) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  			}
  		}
  	}
  	date_default_timezone_set(sync_cfg_time_zone);
  } // __construct()
  
} // class dbSyncDataJob


class dbSyncDataArchives extends dbConnectLE {
	
	const field_id							= 'id';
	const field_archive_id			= 'archive_id';
	const field_archive_number	= 'archive_number';
	const field_archive_date		= 'archive_date';
	const field_archive_name		= 'archive_name';
	const field_archive_type		= 'archive_type';
	const field_backup_type			= 'backup_type';
	const field_status					= 'status';
	const field_timestamp				= 'timestamp';
	
	const archive_type_backup		= 1;
	const archive_type_update		= 2;
	
	const backup_type_complete	= 1;
	const backup_type_mysql			= 2;
	const backup_type_files			= 4;
	
	public $backup_type_array = array(
		array('key' => self::backup_type_complete, 	'value' => sync_type_complete),
		array('key' => self::backup_type_mysql,			'value' => sync_type_mysql),
		array('key' => self::backup_type_files,			'value' => sync_type_files)
	);
	
	public $backup_type_array_text = array(
		self::backup_type_complete => sync_type_complete,
		self::backup_type_mysql => sync_type_mysql,
		self::backup_type_files => sync_type_files,
	);
	
	const status_active					= 1;
	const status_deleted				= 2;
	
	private $createTables 		= false;
	
	public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_sync_data_archives');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
  	$this->addFieldDefinition(self::field_archive_id, "VARCHAR(40) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_archive_number, "INT(11) NOT NULL DEFAULT '1'");
  	$this->addFieldDefinition(self::field_archive_date, "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
  	$this->addFieldDefinition(self::field_archive_name, "VARCHAR(255) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_archive_type, "INT(11) NOT NULL DEFAULT '".self::archive_type_backup."'");
  	$this->addFieldDefinition(self::field_status, "INT(11) NOT NULL DEFAULT '".self::status_active."'");
  	$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP"); 
  	$this->checkFieldDefinitions();
  	// Tabelle erstellen
  	if ($this->createTables) {
  		if (!$this->sqlTableExists()) {
  			if (!$this->sqlCreateTable()) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  			}
  		}
  	}
  	date_default_timezone_set(sync_cfg_time_zone);
  } // __construct()
  
} // class dbSyncDataArchive

class dbSyncDataFiles extends dbConnectLE {
	
	const field_id							= 'id';
	const field_archive_id			= 'archive_id';
	const field_archive_number	= 'archive_number';
	const field_file_type				= 'file_type';
	const field_file_path				= 'file_path';
	const field_file_name				= 'file_name';
	const field_file_checksum		= 'file_checksum';
	const field_file_date				= 'file_date';
	const field_file_size				= 'file_size';
	const field_action					= 'action';
	const field_status					= 'status';
	const field_error_msg				= 'error_message';
	const field_timestamp				= 'timestamp';
	
	const file_type_mysql				= 1;
	const file_type_file				= 2;
	const file_type_unknown			= 4;
	
	const action_add						= 1;
	const action_replace				= 2;
	const action_delete					= 4;
	const action_ignore					= 8;
	
	const status_ok							= 1;
	const status_error					= 2;
	
	private $createTables 		= false;
	
	public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_sync_data_files');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
  	$this->addFieldDefinition(self::field_archive_id, "VARCHAR(40) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_archive_number, "INT(11) NOT NULL DEFAULT '1'");
  	$this->addFieldDefinition(self::field_file_type, "INT(11) NOT NULL DEFAULT '".self::file_type_unknown."'");
  	$this->addFieldDefinition(self::field_file_path, "TEXT NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_file_name, "VARCHAR(255) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_file_checksum, "VARCHAR(40) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_file_date, "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'");
  	$this->addFieldDefinition(self::field_file_size, "INT(11) NOT NULL DEFAULT '0'");
  	$this->addFieldDefinition(self::field_action, "INT(11) NOT NULL DEFAULT '".self::action_add."'");
  	$this->addFieldDefinition(self::field_status, "TINYINT NOT NULL DEFAULT '".self::status_ok."'");
  	$this->addFieldDefinition(self::field_error_msg, "TEXT NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP"); 
  	$this->checkFieldDefinitions();
  	// Tabelle erstellen
  	if ($this->createTables) {
  		if (!$this->sqlTableExists()) {
  			if (!$this->sqlCreateTable()) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  			}
  		}
  	}
  	date_default_timezone_set(sync_cfg_time_zone);
  } // __construct()
	
} // class dbSyncDataFiles

class dbSyncDataProtocol extends dbConnectLE {
	
	const field_id							= 'id';
	const field_archive_id			= 'archive_id';
	const field_archive_number	= 'archive_number';
	const field_job_id					= 'job_id';
	const field_text						= 'text';
	const field_file						= 'file';
	const field_size						= 'size';
	const field_action					= 'action';
	const field_status					= 'status';
	const field_timestamp				= 'timestamp';
	
	const status_ok							= 1;
	const status_error					= 2;
	
	const action_file_add				= 16;
	const action_file_compare		= 128;
	const action_file_delete		= 8;
	const action_file_ignore		= 512;
	const action_file_replace		= 2;
	const action_mysql_add			= 32;
	const action_mysql_delete		= 4;
	const action_mysql_replace	= 1;
	const action_mysql_ignore		= 256;
	const action_unknown				= 64;
	
	private $createTables 		= false;
	
	/**
	 * Constructor
	 * 
	 * @param BOOL $createTables
	 */
	public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_sync_data_protocol');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
  	$this->addFieldDefinition(self::field_archive_id, "VARCHAR(40) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_archive_number, "INT(11) NOT NULL DEFAULT '1'");
  	$this->addFieldDefinition(self::field_job_id, "INT(11) NOT NULL DEFAULT '-1'");
  	$this->addFieldDefinition(self::field_text, "TEXT NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_file, "TEXT NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_size, "INT(11) NOT NULL DEFAULT '-1'");
  	$this->addFieldDefinition(self::field_action, "INT(11) NOT NULL DEFAULT '".self::action_unknown."'");
  	$this->addFieldDefinition(self::field_status, "TINYINT NOT NULL DEFAULT '".self::status_ok."'");
  	$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP"); 
  	$this->checkFieldDefinitions();
  	// Tabelle erstellen
  	if ($this->createTables) {
  		if (!$this->sqlTableExists()) {
  			if (!$this->sqlCreateTable()) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  			}
  		}
  	}
  	date_default_timezone_set(sync_cfg_time_zone);
  } // __construct()
	
  /**
   * Add a new entry into the protocol
   * 
   * @param VARCHAR $archive_id
   * @param INT $archive_number
   * @param INT $job_id
   * @param VARCHAR $text
   * @param VARCHAR $file
   * @param INT $size
   * @param INT $action
   * @param INT $status
   * @return BOOL
   */
  public function addEntry($archive_id, $archive_number, $job_id, $text, $file, $size, $action, $status) {
  	$data = array(
  		self::field_archive_id			=> $archive_id,
  		self::field_archive_number	=> $archive_number,
  		self::field_job_id					=> $job_id,
  		self::field_text						=> $text,
  		self::field_file						=> $file,
  		self::field_size						=> $size,
  		self::field_action					=> $action,
  		self::field_status					=> $status
  	);
  	if (!$this->sqlInsertRecord($data)) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  		return false;
  	}
  	return true;
  } // addEntry()
   
} // class dbSyncDataProtocol

?>