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

global $dbSyncDataCfg;
if (!is_object($dbSyncDataCfg)) $dbSyncDataCfg = new dbSyncDataCfg();

global $dbSyncDataJob;
if (!is_object($dbSyncDataJob)) $dbSyncDataJob = new dbSyncDataJobs();
echo $dbSyncDataJob->getError();

global $dbSyncDataArchive;
if (!is_object($dbSyncDataArchive)) $dbSyncDataArchive = new dbSyncDataArchives();

global $dbSyncDataFile;
if (!is_object($dbSyncDataFile)) $dbSyncDataFile = new dbSyncDataFiles();

global $dbSyncDataProtocol;
if (!is_object($dbSyncDataProtocol)) $dbSyncDataProtocol = new dbSyncDataProtocol();

global $interface;
if (!is_object($interface)) $interface = new syncDataInterface();

class syncDataInterface {
	
	const used_wb_url									= 'used_wb_url';
	const used_wb_path								= 'used_wb_path';
	const used_table_prefix						= 'used_table_prefix';
	
	const total_files									= 'total_files';
	const total_size									= 'total_size';
	const file_list										= 'files.lst';
	const sync_data_ini 							= 'sync_data.ini';
	const archive_list								= 'archive.lst';		
	const section_deleted_tables			= 'deleted_tables';
	const section_deleted_files				= 'deleted_files';
	const section_general							= 'general';
	
	private $error										= '';	
	private $temp_path 								= '';
	private $max_execution_time				= 30;
	private $limit_execution_time			= 25;
	private $memory_limit							= '256M'; 
	private $script_start							= 0;
	private $status										= dbSyncDataJobs::status_undefined;
	private $backup_path							= '';
	private $restore_path							= '';
	
	/**
	 * Constructor
	 */
	public function __construct() {
		global $dbSyncDataCfg;
		$this->script_start = microtime(true);
		$this->temp_path = WB_PATH.'/temp/sync_data/';
		if (!file_exists($this->temp_path)) mkdir($this->temp_path, 0755, true);
		$this->restore_path = $this->temp_path.'restore/';
		if (!file_exists($this->restore_path)) mkdir($this->restore_path, 0755, true);
		$this->memory_limit = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgMemoryLimit);
		@ini_set("memory_limit",$this->memory_limit);
		$this->max_execution_time = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgMaxExecutionTime);
		set_time_limit($this->max_execution_time);
		$this->limit_execution_time = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgLimitExecutionTime);	 
		$this->backup_path = WB_PATH.MEDIA_DIRECTORY.'/sync_data/backup/';
	} // __construct()
	
	/**
	 * Return the Seconds passed since start of the interface
	 * 
	 * @return FLOAT
	 */
	public function getScriptStart() {
		return $this->script_start;
	} // getScriptStart()
	
	/**
	 * Return the used Backup Path
	 * 
	 * @return VARCHAR
	 */
	public function getBackupPath() {
		return $this->backup_path;
	}
	
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
   * Return a array with all tables which are used by 
   * this installation
   * 
   * @return ARRAY $tables
   */
	protected function getTables() { 
		global $database;
		$tables = array();
		$SQL = sprintf("SHOW TABLES FROM %s", DB_NAME);
		if (false ===($query = $database->query($SQL))) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
			return false;
		}
		while (false !== ($table = $query->fetchRow(MYSQL_BOTH))) {
			$tables[] = $table[0];
		}
		return $tables;
	} // getTables

	/**
	 * Read the table $table and save an SQL File for
	 * DROP, CREATE and RESTORE the table
	 * 
	 * @param VARCHAR $table
	 * @return BOOL
	 */
	public function saveTable($table) {
		global $database;
		
		$sql_string = '';
		$sql_string .= sprintf("DROP TABLE IF EXISTS %s;\n", $table);
		$SQL = sprintf("SHOW CREATE TABLE %s", $table);
		if (false ===($query = $database->query($SQL))) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
			return false;
		}
		$create = '';
		while (false !==($row = $query->fetchRow())) {
			$create .= str_replace(chr(10), ' ', trim($row[1]));
		}

		$sql_string .= "$create;\n";
		$SQL = sprintf("SELECT * FROM %s", $table);
		if (false ===($query = $database->query($SQL))) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
			return false;
		}
			
		while (false !==($row = $query->fetchRow(MYSQL_NUM))) {
			$val = '';
			$sql_string .= sprintf("INSERT INTO `%s` VALUES(", $table);
			for ($i=0; $i < count($row);$i++) {
				if (is_null($row[$i])) {
					$val .= 'NULL,';
				}
				else {
					$val .= "'".mysql_real_escape_string($row[$i])."',";
				}
			}
			$val = substr($val,0,strlen($val)-1);
			$sql_string .= sprintf("%s);\n", $val);
		}
		$sql_dir = $this->temp_path.'sql/';
		if (!file_exists($sql_dir)) mkdir($sql_dir, 0755, true);
		file_put_contents($sql_dir.$table.'.sql', $sql_string);
		return true;
	} // saveTable()
	
	/**
	 * Compare two files in multiple steps:
	 * file type, file size and binary compare of contents
	 * 
	 * @param VARCHAR $file_1
	 * @param VARCHAR $file_2
	 * @return BOOL
	 */
	protected function compare_files($file_1, $file_2) {
		if ((false ===($ftype_1 = filetype($file_1))) || (false === ($ftype_2 = filetype($file_2)))) {
			echo "filetype: $file_1 - $file_2<br>";
			return false;
		} 
    if (filetype($file_1) !== filetype($file_2)) return false;
    if (($s1=filesize($file_1)) !== ($s2=filesize($file_2))) return false;
    if (!$file_handle_1 = fopen($file_1, 'rb')) {
    	$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_open, $file_1)));
    	return false;
    }
    if (!$file_handle_2 = fopen($file_2, 'rb')) {
      fclose($file_handle_1);
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_open, $file_2)));
    	return false;
    }
    $same = true;
    while (!feof($file_handle_1) && !feof($file_handle_2)) {
      if (fread($file_handle_1, 4096) !== fread($file_handle_2, 4096)) {
        $same = false;
        break;
      }
    }
    if (feof($file_handle_1) !== feof($file_handle_2)) $same = false;
    fclose($file_handle_1);
    fclose($file_handle_2);
    return $same;
  } // compare_files()
  
	/**
   * Ein mehrdimensionales Array nach einem bestimmten Schluessel und Wert durchsuchen.
   * Ist $like == TRUE wird geprueft ob $value enthalten ist (keine genaue Uebereinstimmung)
   * 
   * @param ARRAY $array
   * @param STR $key
   * @param STR $value
   * @param BOOL $like
   * @return ARRAY
   */
  public function array_search($array, $key, $value, $like=false) {
    $results = array();
    $this->array_search_r($array, $key, $value, $results, $like);
    return $results;
  } // search()

  /**
   * Rekursion fuer array_search()
   * 
   * @param ARRAY $array
   * @param STR $key
   * @param STR $value
   * @param REFERENCE ARRAY $results
   * @param BOOL $like
   */
  protected function array_search_r($array, $key, $value, &$results, $like=false) {
    if (!is_array($array)) return;
    if ((!$like && (isset($array[$key]) && ($array[$key] == $value))) ||
    		($like && (isset($array[$key])) && (stripos($array[$key], $value) !== false))) $results[] = $array;
    foreach ($array as $subarray) $this->array_search_r($subarray, $key, $value, $results, $like);
  } // array_search_r()
  
  /**
   * Write an assosiated ARRAY to a INI File
   * 
   * @param ARRAY $assoc_arr
   * @param VARCHAR $path
   * @param BOOL $has_sections
   * @return BOOL
   */
  protected function write_ini_file($assoc_arr, $path, $has_sections=false) { 
    $content = ""; 
    if ($has_sections) { 
      foreach ($assoc_arr as $key=>$elem) { 
        $content .= "[".$key."]\n"; 
        foreach ($elem as $key2=>$elem2) { 
          if (is_array($elem2)) { 
            for ($i=0;$i<count($elem2);$i++) { 
              $content .= $key2."[] = \"".$elem2[$i]."\"\n"; 
            } 
          } 
          elseif ($elem2=="") $content .= $key2." = \n"; 
          else $content .= $key2." = \"".$elem2."\"\n"; 
        } 
      } 
    } 
    else { 
      foreach ($assoc_arr as $key=>$elem) { 
        if (is_array($elem)) { 
          for ($i=0;$i<count($elem);$i++) { 
            $content .= $key."[] = \"".$elem[$i]."\"\n"; 
          } 
        } 
        elseif ($elem=="") $content .= $key." = \n"; 
        else $content .= $key." = \"".$elem."\"\n"; 
      } 
    } 
    if (!$handle = fopen($path, 'w')) { 
    	$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_handle, $path)));
      return false; 
    } 
    if (!fwrite($handle, $content)) { 
    	$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_write, $path)));
      return false; 
    } 
    fclose($handle); 
    return true; 
  } // write_ini_file()
  
  /**
   * Delete all files of an directory recursive
   * 
   * @param STR $directory
   * @param BOOL $empty - if TRUE directory will not deleted
   */
  public function clearDirectory($directory, $empty=true) { 
    if (substr($directory,-1) == "/") $directory = substr($directory,0,-1);
    if (!file_exists($directory) || !is_dir($directory)) {
      return false;
    } 
    elseif (!is_readable($directory)) {
    	$this->setError(sprintf('[%s - %s] %s', sprintf(snyc_error_dir_not_readable, $directory)));
      return false;
    } 
    else {
      $directoryHandle = opendir($directory); 
      while (false !== ($contents = readdir($directoryHandle))) {
        if ($contents != '.' && $contents != '..') {
          $path = $directory . "/" . $contents;
          if (is_dir($path)) {
            $this->clearDirectory($path, $empty);
          } 
          else {
            unlink($path);
          }
        }
      }
      closedir($directoryHandle);
      if ($empty == false) {
        if (!rmdir($directory)) {
        	$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_rmdir, $directory)));
          return false;
        }
      } 
      return true;
    }
  } // clearDirectory() 
  
  /**
   * Start creation of an new Backup Archive with the desired
   * Archive Name and Archive Type
   * 
   * @param STR $archive_name
   * @param INT $archive_type
   * @param REFERENCE INT $job_id
   * @return INT $status
   */
  public function backupStart($archive_name, $archive_type, &$job_id) { 
  	global $dbSyncDataArchive;
		global $kitTools;
		global $dbSyncDataJob;
		global $dbSyncDataFile;
		
		// neuen Datensatz anlegen
		$archive_id = $kitTools->createGUID();
		
		$data = array(
			dbSyncDataArchives::field_archive_date		=> date('Y-m-d H:i:s'),
			dbSyncDataArchives::field_archive_id			=> $archive_id,
			dbSyncDataArchives::field_archive_name		=> $archive_name,
			dbSyncDataArchives::field_archive_number	=> 1,
			dbSyncDataArchives::field_archive_type		=> dbSyncDataArchives::archive_type_backup,
			dbSyncDataArchives::field_backup_type			=> $archive_type,
			dbSyncDataArchives::field_status					=> dbSyncDataArchives::status_active
		);
    $id = -1;
		if (!$dbSyncDataArchive->sqlInsertRecord($data, $id)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataArchive->getError()));
			return false;
		}
		
		// Job anlegen
		$archive_file = sprintf('%s-%03d.zip', date('Ymd-His', strtotime($data[dbSyncDataArchives::field_archive_date])), 1);
		
		$job_type = dbSyncDataJobs::type_undefined;
		switch ($archive_type):
		case dbSyncDataArchives::backup_type_complete:
			$job_type = dbSyncDataJobs::type_backup_complete; break;
		case dbSyncDataArchives::backup_type_files:
			$job_type = dbSyncDataJobs::type_backup_files; break;
		case dbSyncDataArchives::backup_type_mysql:
			$job_type = dbSyncDataJobs::type_backup_mysql; break;
		endswitch;
		
		$data = array(
			dbSyncDataJobs::field_archive_file		=> $archive_file,
			dbSyncDataJobs::field_archive_number	=> 1,
			dbSyncDataJobs::field_archive_id			=> $archive_id,
			dbSyncDataJobs::field_errors					=> 0,
			dbSyncDataJobs::field_start						=> date('Y-m-d H:i:s'),
			dbSyncDataJobs::field_status					=> dbSyncDataJobs::status_start,
			dbSyncDataJobs::field_type						=> $job_type
		);
		$job_id = -1;
		if (!$dbSyncDataJob->sqlInsertRecord($data, $job_id)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		
		// ggf. bereits existierendes ZIP Archiv loeschen
		$zip_file = $this->temp_path.$archive_file;
		if (file_exists($zip_file)) unlink($zip_file);
		
		if (($job_type == dbSyncDataJobs::type_backup_complete) || ($job_type == dbSyncDataJobs::type_backup_mysql)) {
			// MySQL Sicherung
			if (!$this->backupTables($job_id)) {
				// Fehler oder Timeout beim Sichern der Tabellen
				if ($this->isError()) return false;
				if ($this->status == dbSyncDataJobs::status_time_out) { 
					// Operation vorzeitig beenden...
					return $this->backupInterrupt($job_id, $archive_id, false, 0);
				}
			}
		}
					
		if (($job_type == dbSyncDataJobs::type_backup_complete) || ($job_type == dbSyncDataJobs::type_backup_files)) {
			// Dateien sichern
			if (!$this->backupFiles($job_id)) {
				// Fehler oder Timeout beim Sichern der Dateien
				if ($this->isError()) return false;
				if ($this->status == dbSyncDataJobs::status_time_out) { 
					// Operation vorzeitig beenden...
					return $this->backupInterrupt($job_id, $archive_id, true, 0);
				}
			}
		}
		// Operation beenden
		return $this->backupFinish($job_id, $archive_id, 0);
  } // backupStart()
  
  /**
   * Continue the creation of the Backup Archive specified by $job_id
   * 
   * @param INT $job_id
   * @return INT $status
   */
  public function backupContinue($job_id) {
  	global $dbSyncDataArchive;
		global $kitTools;
		global $dbSyncDataJob;
		global $dbSyncDataFile;
		
		if ($job_id < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_job_id_invalid, $job_id)));
			return false;
		}
		
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
		
		if ($job[dbSyncDataJobs::field_status] == dbSyncDataJobs::status_time_out) {
			// Datensicherung fortsetzen
			$finished = false;
			if ($job[dbSyncDataJobs::field_next_action] == dbSyncDataJobs::next_action_mysql) {
				// Tabellen sichern
				if (!$this->backupTables($job_id)) {
					// Fehler oder Timeout beim Sichern der Dateien
					if ($this->isError()) return false;
					if ($this->status == dbSyncDataJobs::status_time_out) {
						// Operation vorzeitig beenden...
						return $this->backupInterrupt($job_id, $job[dbSyncDataJobs::field_archive_id], false, $job[dbSyncDataJobs::field_total_time]);
					}
				}
				elseif ($job[dbSyncDataJobs::field_type] == dbSyncDataJobs::type_backup_complete) {
					// MySQL Sicherung abgeschlossen
					$where = array(dbSyncDataJobs::field_id => $job_id);
					$data = array(
						dbSyncDataJobs::field_next_action => dbSyncDataJobs::next_action_file,
						dbSyncDataJobs::field_next_file => ''
					);
					if (!$dbSyncDataJob->sqlUpdateRecord($data, $where)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
						return false;
					}
					if (!$this->backupFiles($job_id)) {
						// Fehler oder Timeout beim Sichern der Dateien
						if ($this->isError()) return false;
						if ($this->status == dbSyncDataJobs::status_time_out) {
							// Operation vorzeitig beenden...
							return $this->backupInterrupt($job_id, $job[dbSyncDataJobs::field_archive_id], true, $job[dbSyncDataJobs::field_total_time]);
						}
					}					
					$finished = true;
				}
				else {
					$finished = true;
				}
			}
			
			if ($job[dbSyncDataJobs::field_next_action] == dbSyncDataJobs::next_action_file) {
				// Dateien sichern
				if (!$this->backupFiles($job_id)) {
					// Fehler oder Timeout beim Sichern der Dateien
					if ($this->isError()) return false;
					if ($this->status == dbSyncDataJobs::status_time_out) { 
						// Operation vorzeitig beenden...
						return $this->backupInterrupt($job_id, $job[dbSyncDataJobs::field_archive_id], true, $job[dbSyncDataJobs::field_total_time]);
					}
				}
				$finished = true;
			}
			// Datensicherung ist abgeschlossen
			if ($finished) return $this->backupFinish($job_id, $job[dbSyncDataJobs::field_archive_id], $job[dbSyncDataJobs::field_total_time]);
		}	
		// in allen anderen Faellen ist nichts zu tun
		return false;
  } // backupContinue()
  
  /**
	 * Fuehrt die Datensicherung fuer die MySQL Tabellen durch
	 * 
	 * @param INT $job_id
	 * @return BOOL
	 */
	private function backupTables($job_id) {
		global $dbSyncDataJob;
		global $dbSyncDataArchive;
		global $dbSyncDataFile;
		global $dbSyncDataCfg;
		
		$where = array(dbSyncDataJobs::field_id => $job_id);
		$job = array();
		if (!$dbSyncDataJob->sqlSelectRecord($where, $job)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		$job = $job[0];
		
		$running = (empty($job[dbSyncDataJobs::field_next_file])) ? true : false;
		
		$tables = array();
		if (false === ($tables = $this->getTables())) {
			return false;
		}

		$zip_file = $this->temp_path.$job[dbSyncDataJobs::field_archive_file];
		$zip = new PclZip($zip_file);
		
		$this->status = dbSyncDataJobs::status_undefined; 
		
		// zu ignorierende Tabellen in ein Array schreiben
		$ig_tab = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgIgnoreTables);
		$ig_tab = explode("\n", $ig_tab);
		$ignore_tables = array();
		foreach ($ig_tab as $it) $ignore_tables[] = TABLE_PREFIX.$it;
		foreach ($tables as $table) {
			$this->next_file = $table;
			$ex_time = (int) (microtime(true) - $this->getScriptStart());
			if ($ex_time >= $this->limit_execution_time) {
				$this->status = dbSyncDataJobs::status_time_out; 
				return false;
			}
			// Bei Fortsetzung den Anfang suchen...
			if (!$running && ($table != $job[dbSyncDataJobs::field_next_file])) {
				continue;
			}
      else {
				$running = true;
			}			
			$data = array();
			if (in_array($table, $ignore_tables)) {
				// Tabelle ignorieren
				$data = array(
					dbSyncDataFiles::field_action					=> dbSyncDataFiles::action_ignore,
					dbSyncDataFiles::field_archive_id			=> $job[dbSyncDataJobs::field_archive_id],
					dbSyncDataFiles::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number],
					dbSyncDataFiles::field_file_checksum	=> 0,
					dbSyncDataFiles::field_file_date			=> '0000-00-00 00:00:00',
					dbSyncDataFiles::field_file_name			=> $table.'.sql',
					dbSyncDataFiles::field_file_path			=> '',
					dbSyncDataFiles::field_file_type			=> dbSyncDataFiles::file_type_mysql,
					dbSyncDataFiles::field_file_size			=> 0,
					dbSyncDataFiles::field_status					=> dbSyncDataFiles::status_ok,
					dbSyncDataFiles::field_error_msg			=> ''
				);
			}
			else {
				$this->saveTable($table);
        $list = array();
				if (0 == ($list = $zip->add($this->temp_path.'sql/'.$table.'.sql', PCLZIP_OPT_ADD_PATH, 'sql', PCLZIP_OPT_REMOVE_PATH, $this->temp_path.'sql/'))) {
					// Fehler beim Hinzufuegen der Datei
					$data = array(
						dbSyncDataFiles::field_action					=> dbSyncDataFiles::action_add,
						dbSyncDataFiles::field_archive_id			=> $job[dbSyncDataJobs::field_archive_id],
						dbSyncDataFiles::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number],
						dbSyncDataFiles::field_file_checksum	=> 0,
						dbSyncDataFiles::field_file_date			=> '0000-00-00 00:00:00',
						dbSyncDataFiles::field_file_name			=> $table.'.sql',
						dbSyncDataFiles::field_file_path			=> '',
						dbSyncDataFiles::field_file_type			=> dbSyncDataFiles::file_type_mysql,
						dbSyncDataFiles::field_file_size			=> 0,
						dbSyncDataFiles::field_status					=> dbSyncDataFiles::status_error,
						dbSyncDataFiles::field_error_msg			=> $zip->errorInfo(true)
					);
				}
				else {
					$data = array(
						dbSyncDataFiles::field_action					=> dbSyncDataFiles::action_add,
						dbSyncDataFiles::field_archive_id			=> $job[dbSyncDataJobs::field_archive_id],
						dbSyncDataFiles::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number],
						dbSyncDataFiles::field_file_checksum	=> dechex($list[0]['crc']),
						dbSyncDataFiles::field_file_date			=> date('Y-m-d H:i:s', $list[0]['mtime']),
						dbSyncDataFiles::field_file_name			=> $table.'.sql',
						dbSyncDataFiles::field_file_path			=> '',
						dbSyncDataFiles::field_file_type			=> dbSyncDataFiles::file_type_mysql,
						dbSyncDataFiles::field_file_size			=> $list[0]['size'],
						dbSyncDataFiles::field_status					=> dbSyncDataFiles::status_ok,
						dbSyncDataFiles::field_error_msg			=> ''
					);
				}
			}
			if (!$dbSyncDataFile->sqlInsertRecord($data)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
				return false;
			}			
		}
		return true;
	} // backupTables()
  
	/**
	 * Unterbricht das Backup wegen Zeitueberschreitung, sichert den aktuellen Stand
	 * und ermoeglicht die Fortsetzung
	 * 
	 * @param INT $job_id
	 * @param STR $archive_id
	 * @param BOOL $process_files
	 * @param FLOAT $old_total_time
	 * @return STR dialog on success BOOL FALSE on error
	 */
	private function backupInterrupt($job_id, $archive_id, $process_files=true, $old_total_time=0) {
		global $dbSyncDataJob;
		
		$data = array(
			dbSyncDataJobs::field_status				=> dbSyncDataJobs::status_time_out,
			dbSyncDataJobs::field_last_message	=> 'TIME_OUT',
			dbSyncDataJobs::field_total_time		=> (microtime(true) - $this->getScriptStart())+$old_total_time,
			dbSyncDataJobs::field_next_action		=> ($process_files) ? dbSyncDataJobs::next_action_file : dbSyncDataJobs::next_action_mysql,
			dbSyncDataJobs::field_next_file			=> $this->next_file
		);
		$where = array(
			dbSyncDataJobs::field_id	=> $job_id
		);
		if (!$dbSyncDataJob->sqlUpdateRecord($data, $where)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		// Status zurueckgeben	
		return dbSyncDataJobs::status_time_out;
	} // interruptBackup()
	
	/**
	 * Schliesst das Backup ab und gibt eine zusammenfassende Meldung aus
	 * 
	 * @param INT $job_id
	 * @param STR $archive_id
	 * @param FLOAT $old_total_time
	 * @return STR message on succes or BOOL FALSE on error
	 */
	private function backupFinish($job_id, $archive_id, $old_total_time) {
		global $dbSyncDataJob;
		global $dbSyncDataFile;
		global $dbSyncDataArchive;
		global $kitTools;
		
		// Datensicherung abgeschlossen
		$data = array(
			dbSyncDataJobs::field_status				=> dbSyncDataJobs::status_finished,
			dbSyncDataJobs::field_last_message	=> 'FINISHED',
			dbSyncDataJobs::field_total_time		=> (microtime(true) - $this->script_start) + $old_total_time,
			dbSyncDataJobs::field_next_action		=> dbSyncDataJobs::next_action_none,
			dbSyncDataJobs::field_next_file			=> '',
			dbSyncDataJobs::field_end						=> date('Y-m-d H:i:s')
		);
		$where = array(
			dbSyncDataJobs::field_id	=> $job_id
		);
		if (!$dbSyncDataJob->sqlUpdateRecord($data, $where)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		$job = array();
		if (!$dbSyncDataJob->sqlSelectRecord($where, $job)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		$job = $job[0];
		
		$where = array(dbSyncDataArchives::field_archive_id => $job[dbSyncDataJobs::field_archive_id]);
		$archive = array();
		if (!$dbSyncDataArchive->sqlSelectRecord($where, $archive)) {
			$this->setError(sprintf('[%s _ %s] %s', __METHOD__, __LINE__, $dbSyncDataArchive->getError()));
			return false;
		}
		if (count($archive) < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_archive_id_invalid, $job[dbSyncDataJobs::field_archive_id])));
			return false;
		}
		$archive = $archive[0];
		
		$zip_file = $this->temp_path.$job[dbSyncDataJobs::field_archive_file];
		$zip = new PclZip($zip_file);
		
		// Dateiliste zum Archiv hinzufuegen
		if (($job[dbSyncDataJobs::field_type] == dbSyncDataJobs::type_backup_complete) ||
				($job[dbSyncDataJobs::field_type] == dbSyncDataJobs::type_backup_files)) {
			$list = array();
			if (0 == ($list = $zip->add($this->temp_path.self::file_list, '', $this->temp_path))) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $zip->errorInfo(true)));
				return false;
			}
		}
		
		// Anzahl und Umfang der bisher gesicherten Dateien ermitteln
		$SQL = sprintf(	"SELECT COUNT(%s), SUM(%s) FROM %s WHERE %s='%s' AND %s='%s' AND %s='%s'",
										dbSyncDataFiles::field_file_name,
										dbSyncDataFiles::field_file_size,
										$dbSyncDataFile->getTableName(),
										dbSyncDataFiles::field_archive_id,
										$archive_id, //$job[dbSyncDataJobs::field_archive_id],
										dbSyncDataFiles::field_status,
										dbSyncDataFiles::status_ok,
										dbSyncDataFiles::field_action,
										dbSyncDataFiles::action_add);
		$files = array();
		if (!$dbSyncDataFile->sqlExec($SQL, $files)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
			return false;
		}
		
		// Informationen ueber das Backup in der sync_data.ini festhalten
		$ini = $job;
		$ini[self::used_wb_path] = WB_PATH;
		$ini[self::used_wb_url] = WB_URL;
		$ini[self::used_table_prefix] = TABLE_PREFIX;
		$ini[self::total_files] = $files[0][sprintf('COUNT(%s)', dbSyncDataFiles::field_file_name)];
		$ini[self::total_size] = $files[0][sprintf('SUM(%s)', dbSyncDataFiles::field_file_size)];
		
		$ini_sections = array(
			self::section_general					=> $ini,
		//	self::section_deleted_tables	=> '',	//$mysql_deleted,
		//	self::section_deleted_files		=> '' 	//$files_deleted
		);
		
		if (!$this->write_ini_file($ini_sections, $this->temp_path.self::sync_data_ini, true)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
			return false;
		}
		
		// sync_data.ini in das Archiv aufnehmen
		if (0 == ($list = $zip->add($this->temp_path.self::sync_data_ini, '', $this->temp_path))) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $zip->errorInfo(true)));
			return false;
		}
		
		if (!file_exists($this->backup_path)) {
			if (!mkdir($this->backup_path, 0755, true)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_mkdir, $this->backup_path)));
				return false;
			}
		}
		$archive_name = (!empty($archive[dbSyncDataArchives::field_archive_name])) ? page_filename($archive[dbSyncDataArchives::field_archive_name].'.zip') : $job[dbSyncDataJobs::field_archive_file];
		
		if (!copy($this->temp_path.$job[dbSyncDataJobs::field_archive_file], $this->backup_path.$archive_name)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_copy_file, $this->temp_path.$job[dbSyncDataJobs::field_archive_file], $this->backup_path.$archive_name)));
			return false;
		}
		
		// temporaeres Verzeichnis aufraeumen
		if (!$this->clearDirectory($this->temp_path, true) && $this->isError()) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
			return false;
		}
		// return status
		return dbSyncDataJobs::status_finished;
	} // backupFinish()
	
	/**
	 * Iterate directory tree very efficient
	 * Function postet from donovan.pp@gmail.com at
	 * http://www.php.net/manual/de/function.scandir.php 
	 * 
	 * @param STR $dir
	 * @return ARRAY - directoryTree
	 */
	public function directoryTree($dir) {
		if (substr($dir,-1) == "/") $dir = substr($dir,0,-1);    
   	$path = array();
    $stack = array();
   	$stack[] = $dir;
   	while ($stack) {
    	$thisdir = array_pop($stack);
    	if (false !== ($dircont = scandir($thisdir))) {
    		$i=0;
    		while (isset($dircont[$i])) {
    			if ($dircont[$i] !== '.' && $dircont[$i] !== '..') {
    				$current_file = "{$thisdir}/{$dircont[$i]}";
    				if (is_file($current_file)) {
    					$path[] = "{$thisdir}/{$dircont[$i]}";
    				} 
    				elseif (is_dir($current_file)) {
    					$stack[] = $current_file;
    				}
    			}
    			$i++;
    		}
    	}
   	}
   	return $path;
	} // directoryTree()
	
	/**
	 * Sichert die Dateien der Installation und fuegt sie dem Archiv hinzu
	 * 
	 * @param INT $job_id
	 * @return BOOL
	 */
	private function backupFiles($job_id) {
		global $dbSyncDataJob;
		global $dbSyncDataArchive;
		global $dbSyncDataFile;
		global $dbSyncDataCfg;
		
		$where = array(dbSyncDataJobs::field_id => $job_id);
		$job = array();
		if (!$dbSyncDataJob->sqlSelectRecord($where, $job)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		$job = $job[0];
		
		$running = (empty($job[dbSyncDataJobs::field_next_file])) ? true : false;
		
		$zip_file = $this->temp_path.$job[dbSyncDataJobs::field_archive_file];
		$zip = new PclZip($zip_file);
		
		$this->status = dbSyncDataJobs::status_undefined; 
		
		if ($running) {
			// on first call create file_list and store it at /temp directory
			$path = $this->directoryTree(WB_PATH);
			$fp = fopen($this->temp_path.self::file_list, 'w');
			foreach($path as $values) fputs($fp, $values."\n");
			fclose($fp); 			
		}
		
		$files = file($this->temp_path.self::file_list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$max = count($files);
		
		$ig_dir = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgIgnoreDirectories);
		$ig_dir = explode("\n", $ig_dir);
		$ignore_directories = array();
		foreach ($ig_dir as $id) $ignore_directories[] = WB_PATH.$id;
		$ignore_extensions = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgIgnoreFileExtensions);
		
		for ($i=0; $i < $max; $i++) {
			$file = trim($files[$i]);
			$this->next_file = $file; 
			$ex_time = (int) (microtime(true) - $this->getScriptStart());
			if ($ex_time >= $this->limit_execution_time) {
				$this->status = dbSyncDataJobs::status_time_out; 
				return false;
			}
			// leere Zeilen ueberspringen
			if (empty($file)) continue;
			// Verzeichnisse ueberspringen
			if (is_dir($file)) continue;
			// Bei Fortsetzung den Anfang suchen...
			if (!$running && ($file != $job[dbSyncDataJobs::field_next_file])) {
				continue;
			}
			else {
				$running = true;
			}
			
			foreach ($ignore_directories as $ig_dir) {
				if (strpos($file, $ig_dir) !== false) {
					$data = array(
						dbSyncDataFiles::field_action					=> dbSyncDataFiles::action_ignore,
						dbSyncDataFiles::field_archive_id			=> $job[dbSyncDataJobs::field_archive_id],
						dbSyncDataFiles::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number],
						dbSyncDataFiles::field_file_checksum	=> 0,
						dbSyncDataFiles::field_file_date			=> '0000-00-00 00:00:00',
						dbSyncDataFiles::field_file_name			=> basename($file),
						dbSyncDataFiles::field_file_path			=> str_replace(WB_PATH, '', $file),
						dbSyncDataFiles::field_file_type			=> dbSyncDataFiles::file_type_file,
						dbSyncDataFiles::field_file_size			=> 0,
						dbSyncDataFiles::field_status					=> dbSyncDataFiles::status_ok,
						dbSyncDataFiles::field_error_msg			=> ''
					);
					if (!$dbSyncDataFile->sqlInsertRecord($data)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
						return false;
					}
					continue 2; // continue with the next file in the for loop!	
				}
			}		
			
			// pruefen ob es sich um eine ignorierte Dateiendung handelt
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			if (in_array($ext, $ignore_extensions)) continue;
				
      $data = array();
      $list = array();
      if (0 == ($list = $zip->add($file, PCLZIP_OPT_ADD_PATH, 'files', PCLZIP_OPT_REMOVE_PATH, WB_PATH))) {
				// Fehler beim Hinzufuegen der Datei
				$data = array(
					dbSyncDataFiles::field_action					=> dbSyncDataFiles::action_add,
					dbSyncDataFiles::field_archive_id			=> $job[dbSyncDataJobs::field_archive_id],
					dbSyncDataFiles::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number],
					dbSyncDataFiles::field_file_checksum	=> 0,
					dbSyncDataFiles::field_file_date			=> '0000-00-00 00:00:00',
					dbSyncDataFiles::field_file_name			=> basename($file),
					dbSyncDataFiles::field_file_path			=> str_replace(WB_PATH, '', $file),
					dbSyncDataFiles::field_file_type			=> dbSyncDataFiles::file_type_file,
					dbSyncDataFiles::field_file_size			=> 0,
					dbSyncDataFiles::field_status					=> dbSyncDataFiles::status_error,
					dbSyncDataFiles::field_error_msg			=> $zip->errorInfo(true)
				); 
			}
			else {
				$data = array(
					dbSyncDataFiles::field_action					=> dbSyncDataFiles::action_add,
					dbSyncDataFiles::field_archive_id			=> $job[dbSyncDataJobs::field_archive_id],
					dbSyncDataFiles::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number],
					dbSyncDataFiles::field_file_checksum	=> dechex($list[0]['crc']),
					dbSyncDataFiles::field_file_date			=> date('Y-m-d H:i:s', $list[0]['mtime']),
					dbSyncDataFiles::field_file_name			=> basename($file),
					dbSyncDataFiles::field_file_path			=> str_replace(WB_PATH, '', $file),
					dbSyncDataFiles::field_file_type			=> dbSyncDataFiles::file_type_file,
					dbSyncDataFiles::field_file_size			=> $list[0]['size'],
					dbSyncDataFiles::field_status					=> dbSyncDataFiles::status_ok,
					dbSyncDataFiles::field_error_msg			=> ''
				);
			}
			if (!$dbSyncDataFile->sqlInsertRecord($data)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
				return false;
			}	
		}
		return true;	
	} // backupFiles()
	
	/**
	 * Prepare the restore of datas from a $backup_archive.
	 * This function writes the file list from the archiv ZIP File to the 
	 * temporary directory and return the content of the sync_data.ini
	 * by reference 
	 * 
	 * @param STR $backup_archive
	 * @param REFERENCE ARRAY $sync_data_ini
	 */
	public function restoreInfo($backup_archive, &$sync_data_ini) {
		global $kitTools;
		
		// Restore vorbereiten
  	if (!file_exists($this->temp_path)) {
  		if (!mkdir($this->temp_path, 0755, true)) {
  			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_mkdir, str_replace(WB_PATH, '', $this->temp_path))));
  			return false;
  		}
  	}
  	
  	// temporaeres Verzeichnis aufraeumen
		if (!$this->clearDirectory($this->temp_path, true) && $this->isError()) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
			return false;
		}
  	
  	// ZIP initialisieren und Dateiliste auslesen
  	$zip = new PclZip(WB_PATH.$backup_archive);
  	$list = array();
  	if (0 == ($list = $zip->listContent())) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $zip->errorInfo(true)));
  		return false;
  	}
  	
  	// Liste des Archivs im temporaeren Verzeichnis speichern
  	if (!file_put_contents($this->temp_path.self::archive_list, serialize($list))) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_write, $this->temp_path.self::archive_list)));
  		return false;
  	}
  	
  	// sync_data.ini in der Dateiliste suchen
  	$restore_info = $this->array_search($list, 'filename', self::sync_data_ini);
  	if (count($restore_info) != 1) {
  		// sync_data.ini nicht gefunden!
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_archive_missing_ini, basename($backup_archive))));
  		return false;
  	}
  	
  	// die sync_data.ini ins TEMP Verzeichnis schreiben
  	if (0 == ($list = $zip->extractByIndex($restore_info[0]['index'], $kitTools->correctBackslashToSlash($this->temp_path), ''))) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $zip->errorInfo(true)));
  		return false;
  	}
  	
  	if (false === ($sync_data_ini = parse_ini_file($this->temp_path.self::sync_data_ini, true))) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_read, str_replace(WB_PATH, '', $this->temp_path.self::sync_data_ini))));
  		return false;
  	}
  	return true;
	} // restoreInfo()
	
	/**
	 * Start the process of restoring datas from an archive to the active
	 * installation.
	 * 
	 * @param STR $backup_archive - name of the used archive *.zip file
	 * @param BOOL $replace_prefix - replace TABLE_PREFIX?
	 * @param BOOL $replace_url - replace WB_URL?
	 * @param INT $restore_type - type of restoring: complete, MySQL only or Files only
	 * @param INT $restore_mode - mode of restoring: overwrite all, date & time compare or binary compare
	 * @param BOOL $ignore_config - skip config.php 
	 * @param BOOL $ignore_htaccess - skip .htaccess
	 * @param BOOL $delete_files - delete files not existing in the archive file
	 * @param BOOL $delete_tables - delete tables not existing in the archive file
	 * @param REFERENCE INT $job_id - integer ID of the new createad syncData Job
	 * @return INT|BOOL - integer value of the next action or FALSE on error
	 */
	public function restoreStart($backup_archive, $replace_prefix, $replace_url, $restore_type, $restore_mode, $ignore_config, $ignore_htaccess, $delete_files, $delete_tables, &$job_id)  {
  	global $kitTools;
		global $dbSyncDataJob;
		global $dbSyncDataProtocol;  	
		 	
  	// existiert die sync_data.ini im /temp Verzeichnis?
  	if (!file_exists($this->temp_path.self::sync_data_ini)) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_not_exists, self::sync_data_ini)));
  		return false;
  	}
  	// sync_data.ini auslesen
  	if (false === ($ini_data = parse_ini_file($this->temp_path.self::sync_data_ini, true))) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_read, str_replace(WB_PATH, '', $this->temp_path.self::sync_data_ini))));
  		return false;
  	}
  	// neuen JOB fuer den Restore anlegen
  	if (in_array(dbSyncDataJobs::type_restore_mysql, $restore_type) && in_array(dbSyncDataJobs::type_restore_files, $restore_type)) {
  		$type = dbSyncDataJobs::type_restore_complete;
  		$next_action = dbSyncDataJobs::next_action_mysql;
  	}
  	elseif (in_array(dbSyncDataJobs::type_restore_mysql, $restore_type)) {
  		$type = dbSyncDataJobs::type_restore_mysql;
  		$next_action = dbSyncDataJobs::next_action_mysql;
  	}
  	else {
  		$type = dbSyncDataJobs::type_restore_files;
  		$next_action = dbSyncDataJobs::next_action_file;
  	}
  	$job = array(
  		dbSyncDataJobs::field_archive_file					=> $backup_archive,
  		dbSyncDataJobs::field_archive_id						=> $ini_data[syncDataInterface::section_general][dbSyncDataJobs::field_archive_id],
  		dbSyncDataJobs::field_archive_number				=> $ini_data[syncDataInterface::section_general][dbSyncDataJobs::field_archive_number],
  		dbSyncDataJobs::field_errors								=> 0,
  		dbSyncDataJobs::field_last_error						=> '',
  		dbSyncDataJobs::field_last_message					=> '',
  		dbSyncDataJobs::field_next_action						=> $next_action,
  		dbSyncDataJobs::field_next_file							=> '',
  		dbSyncDataJobs::field_replace_table_prefix	=> $replace_prefix ? 1 : 0,
  		dbSyncDataJobs::field_replace_wb_url				=> $replace_url ? 1 : 0,
  		dbSyncDataJobs::field_restore_mode					=> $restore_mode,
  		dbSyncDataJobs::field_start									=> date('Y-m-d H:i:s'),
  		dbSyncDataJobs::field_status								=> dbSyncDataJobs::status_start,
  		dbSyncDataJobs::field_total_time						=> 0,
  		dbSyncDataJobs::field_type									=> $type,
  		dbSyncDataJobs::field_ignore_config					=> $ignore_config ? 1 : 0,
  		dbSyncDataJobs::field_ignore_htaccess				=> $ignore_htaccess ? 1 : 0,
  		dbSyncDataJobs::field_delete_files					=> $delete_files ? 1 : 0,
  		dbSyncDataJobs::field_delete_tables					=> $delete_tables ? 1 : 0
  	);
  	if (!$dbSyncDataJob->sqlInsertRecord($job, $job_id)) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
  		return false;
  	}
  	$finished = false;
  	if ($job[dbSyncDataJobs::field_next_action] == dbSyncDataJobs::next_action_mysql) {
  		// erste Aktion: MySQL Daten wieder herstellen
  		if (!$this->restoreTables($job_id)) {
  			// Fehler oder Timeout beim Sichern der Dateien
				if ($this->isError()) return false;
				if ($this->status == dbSyncDataJobs::status_time_out) { 
					// Operation vorzeitig beenden...
					return $this->restoreInterrupt($job_id, $job[dbSyncDataJobs::field_archive_id], false, $job[dbSyncDataJobs::field_total_time]);
				}
  		}
  		if ($job[dbSyncDataJobs::field_type] == dbSyncDataJobs::type_restore_complete) {
				// die MySQL Ruecksicherung ist abgeschlossen
				$where = array(dbSyncDataJobs::field_id => $job_id);
				$job[dbSyncDataJobs::field_next_action] = dbSyncDataJobs::next_action_file;
				$job[dbSyncDataJobs::field_next_file]		= '';
				if (!$dbSyncDataJob->sqlUpdateRecord($job, $where)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
					return false;
				}
  		}
  		else {
  			$finished = true;
  		}
  	}
  	if ($job[dbSyncDataJobs::field_next_action] == dbSyncDataJobs::next_action_file) {
  		if (!$this->restoreFiles($job_id)) {
				// Fehler oder Timeout beim Ruecksichern der Dateien
				if ($this->isError()) return false;
				if ($this->status == dbSyncDataJobs::status_time_out) { 
					// Operation vorzeitig beenden...
					return $this->restoreInterrupt($job_id, $job[dbSyncDataJobs::field_archive_id], true, $job[dbSyncDataJobs::field_total_time]);
				}
			}					
			$finished = true;
  	}

  	// Datensicherung ist abgeschlossen
		if ($finished) return $this->restoreFinish($job_id, $job[dbSyncDataJobs::field_archive_id], $job[dbSyncDataJobs::field_total_time]);
  	
		// in allen anderen Faellen ist nichts zu tun...
  	return false;  	
  } // restoreStart()
	
  /**
   * Continue the restoring process for the desired Job ID
   * 
   * @param INT $job_id
   * @return INT|BOOL integer value of the next action or FALSE on error
   */
  public function restoreContinue($job_id) {
  	global $kitTools;
		global $dbSyncDataJob;
		global $dbSyncDataProtocol;  	
		
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
		
		$finished = false;
  	if ($job[dbSyncDataJobs::field_next_action] == dbSyncDataJobs::next_action_mysql) {
  		// erste Aktion: MySQL Daten wieder herstellen
  		if (!$this->restoreTables($job_id)) {
  			// Fehler oder Timeout beim Sichern der Dateien
				if ($this->isError()) return false;
				if ($this->status == dbSyncDataJobs::status_time_out) { 
					// Operation vorzeitig beenden...
					return $this->restoreInterrupt($job_id, $job[dbSyncDataJobs::field_archive_id], false, $job[dbSyncDataJobs::field_total_time]);
				}
  		}
  		if ($job[dbSyncDataJobs::field_type] == dbSyncDataJobs::type_restore_complete) {
				// die MySQL Ruecksicherung ist abgeschlossen
				$where = array(dbSyncDataJobs::field_id => $job_id);
				$job[dbSyncDataJobs::field_next_action] = dbSyncDataJobs::next_action_file;
				$job[dbSyncDataJobs::field_next_file]		= '';
				if (!$dbSyncDataJob->sqlUpdateRecord($job, $where)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
					return false;
				}
  		}
  		else {
  			$finished = true;
  		}
  	}
  	if ($job[dbSyncDataJobs::field_next_action] == dbSyncDataJobs::next_action_file) {
  		if (!$this->restoreFiles($job_id)) {
				// Fehler oder Timeout beim Ruecksichern der Dateien
				if ($this->isError()) return false;
				if ($this->status == dbSyncDataJobs::status_time_out) { 
					// Operation vorzeitig beenden...
					return $this->restoreInterrupt($job_id, $job[dbSyncDataJobs::field_archive_id], true, $job[dbSyncDataJobs::field_total_time]);
				}
			}					
			$finished = true;
  	}

  	// Datensicherung ist abgeschlossen
		if ($finished) return $this->restoreFinish($job_id, $job[dbSyncDataJobs::field_archive_id], $job[dbSyncDataJobs::field_total_time]);
  	
		// in allen anderen Faellen ist nichts zu tun...
  	return false;  	
		
  } // restoreContinue()
  
  /**
   * Restore Tables as specified
   * 
   * @param INT $job_id
   * @return BOOL
   */
	private function restoreTables($job_id) { 
		global $kitTools;
		global $dbSyncDataJob;
		global $dbSyncDataProtocol; 
		global $database; 	
		global $dbSyncDataCfg;

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
		
		// existiert die Dateiliste im /temp Verzeichnis?
  	if (false === ($list = unserialize(file_get_contents($this->temp_path.self::archive_list)))) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_read, $this->temp_path.self::archive_list)));
  		return false;
  	}
  	
  	// get all sql/ files
		$restore_info = $this->array_search($list, 'filename', 'sql/', true);
  	if (count($restore_info) < 1) {
  		// keine MySQL Dateien gefunden!
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sync_error_file_list_no_mysql_files));
  		return false;
  	}
  	
  	// existiert die sync_data.ini im /temp Verzeichnis?
  	if (!file_exists($this->temp_path.self::sync_data_ini)) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_not_exists, self::sync_data_ini)));
  		return false;
  	}
  	// sync_data.ini auslesen
  	if (false === ($ini_data = parse_ini_file($this->temp_path.self::sync_data_ini, true))) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_read, str_replace(WB_PATH, '', $this->temp_path.self::sync_data_ini))));
  		return false;
  	}
 	  
  	// ZIP initialisieren
  	$zip = new PclZip(WB_PATH.$job[dbSyncDataJobs::field_archive_file]);
		$running = (empty($job[dbSyncDataJobs::field_next_file])) ? true : false;

		// zu ignorierende Tabellen in ein Array schreiben
		$ig_tab = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgIgnoreTables);
		$ig_tab = explode("\n", $ig_tab);
		$ignore_tables = array();
		foreach ($ig_tab as $it) $ignore_tables[] = TABLE_PREFIX.$it;
		
		// check if tables should be deleted which are not in archive
		if ($running && ($job[dbSyncDataJobs::field_delete_tables] == 1))  {
			$tables = $this->getTables();
			$check_tables = array();
			foreach ($restore_info as $table) {
				$restore_table_name = str_replace('.sql', '', basename($table['filename']));
				//$replace_table_name = str_replace($ini_data[syncDataInterface::section_general][self::used_table_prefix], TABLE_PREFIX, $restore_table_name);
				$replace_table_name = TABLE_PREFIX.substr($restore_table_name,strlen($ini_data[syncDataInterface::section_general][self::used_table_prefix])); 		
				$check_tables[] = $replace_table_name;
			}
			foreach ($tables as $table) {
				if (in_array($table, $ignore_tables)) continue;
				if (!in_array($table, $check_tables)) {
					// delete tables
					$SQL = sprintf("DROP TABLE IF EXISTS %s", $table);
					$database->query($SQL);
					if ($database->is_error()) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
	  				$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
			  																	$job[dbSyncDataJobs::field_archive_number], 
			  																	$job_id, 
			  																	$this->getError(), 
			  																	$table, 
			  																	0,
			  																	dbSyncDataProtocol::action_mysql_delete,
			  																	dbSyncDataProtocol::status_error);
	  				return false;
					}
					$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
			  																$job[dbSyncDataJobs::field_archive_number], 
			  																$job_id, 
			  																sprintf(sync_protocol_table_delete, $table), 
			  																$table, 
			  																0,
			  																dbSyncDataProtocol::action_mysql_delete,
			  																dbSyncDataProtocol::status_ok);
				}
			}
		} // delete tables
		
		// check if tables should be deleted by sync_data.ini command
		if ($running && isset($ini_data[self::section_deleted_tables])) {
			foreach ($ini_data[self::section_deleted_tables] as $table) {
				$delete_table = str_replace('.sql', '', $table);
				//$delete_table = str_replace($ini_data[syncDataInterface::section_general][self::used_table_prefix], TABLE_PREFIX, $delete_table);
				$delete_table = TABLE_PREFIX.substr($delete_table,strlen($ini_data[syncDataInterface::section_general][self::used_table_prefix]));
  			$SQL = sprintf("DROP TABLE IF EXISTS %s", $delete_table);
				$database->query($SQL);
				if ($database->is_error()) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
					$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
		  																	$job[dbSyncDataJobs::field_archive_number], 
		  																	$job_id, 
		  																	$this->getError(), 
		  																	$delete_table, 
		  																	0,
		  																	dbSyncDataProtocol::action_mysql_delete,
		  																	dbSyncDataProtocol::status_error);
		  		return false;
				}
				$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
		  																$job[dbSyncDataJobs::field_archive_number], 
		  																$job_id, 
		  																sprintf(sync_protocol_table_delete, $table), 
		  																$delete_table, 
		  																0,
		  																dbSyncDataProtocol::action_mysql_delete,
		  																dbSyncDataProtocol::status_ok);
			}
		}
		
		// MySQL Tabellen durchlaufen
  	foreach ($restore_info as $table) {
  		// grant that table is really from sql/ directory within the ZIP archive and not any subdirectory
  		if (strpos($table['filename'], 'sql/') != 0) continue;
  		// Tabelle festhalten
  		$this->next_file = $table['filename'];
			$ex_time = (int) (microtime(true) - $this->getScriptStart());
			if ($ex_time >= $this->limit_execution_time) {
				// Zeitueberschreitung, Abbruch
				$this->status = dbSyncDataJobs::status_time_out; 
				return false;
			}
			// Bei Fortsetzung den Anfang suchen...
			if (!$running && ($table['filename'] != $job[dbSyncDataJobs::field_next_file])) {
				continue;
			}
      else {
				$running = true;
			}			
			$restore_table_name = str_replace('.sql', '', basename($table['filename']));
  		//$replace_table_name = str_replace($ini_data[syncDataInterface::section_general][self::used_table_prefix], TABLE_PREFIX, $restore_table_name);
  		$replace_table_name = TABLE_PREFIX.substr($restore_table_name,strlen($ini_data[syncDataInterface::section_general][self::used_table_prefix]));
  		
  		if (in_array($replace_table_name, $ignore_tables)) {
				// this table will be ignored
				$dbSyncDataProtocol->addEntry(	$job[dbSyncDataJobs::field_archive_id], 
	  																	$job[dbSyncDataJobs::field_archive_number], 
	  																	$job_id, 
	  																	sprintf(sync_protocol_table_ignored, $replace_table_name), 
	  																	$replace_table_name, 
	  																	$table['size'],
	  																	dbSyncDataProtocol::action_mysql_ignore,
	  																	dbSyncDataProtocol::status_ok);
	  		// jump to next table
	  		continue;
			}
			// gesicherte Tabelle entpacken
  		if (0 == ($list = $zip->extractByIndex($table['index'], $kitTools->correctBackslashToSlash($this->restore_path), ''))) {
  			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $zip->errorInfo(true)));
  			return false;
  		}
  			
  		if ((($job[dbSyncDataJobs::field_replace_table_prefix] == 1) && ($ini_data[syncDataInterface::section_general][self::used_table_prefix] != TABLE_PREFIX)) ||
  				(($job[dbSyncDataJobs::field_replace_wb_url] == 1) && ($ini_data[syncDataInterface::section_general][self::used_wb_url] != WB_URL))) {
  			// TABLE_PREFIX und/oder WB_URL muessen geaendert werden, Tabelle temporaer schreiben und aktualisieren
  			$sql_file = file_get_contents($kitTools->correctBackslashToSlash($this->restore_path.$table['filename']));
 				if ($job[dbSyncDataJobs::field_replace_table_prefix] == 1) {
  				// TABLE_PREFIX muss geaendert werden
  				$sql_file = str_replace($restore_table_name, $replace_table_name, $sql_file);
  			}
  			if ($job[dbSyncDataJobs::field_replace_wb_url] == 1) {
  				// WB_URL muss geaendert werden
  				$sql_file = str_replace($ini_data[syncDataInterface::section_general][self::used_wb_url], WB_URL, $sql_file);
  			}
 				file_put_contents($this->restore_path.$table['filename'], $sql_file);
  		}
  		// pruefen ob Tabelle aktuell existiert
  		$SQL = sprintf("SHOW TABLE STATUS LIKE '%s'", $replace_table_name);
	  	if (false ===($query = $database->query($SQL))) {
	  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
	  		return false;
	  	}
	  	$table_exists = ($query->numRows() > 0) ? true : false;
	  	$replace_table = false;
	  	if ($table_exists && (($job[dbSyncDataJobs::field_restore_mode] == dbSyncDataJobs::mode_changed_date_size) || ($job[dbSyncDataJobs::field_restore_mode] == dbSyncDataJobs::mode_changed_date_size))) {
	  		// Tabelle existiert und soll nur bei Abweichungen ersetzt werden
	  		$this->saveTable($replace_table_name);
	  		if (!$this->compare_files($this->temp_path.'restore/'.$table['filename'], $this->temp_path.'sql/'.$replace_table_name.'.sql')) {
	  			$replace_table = true;
	  		}
	  	}
	  	elseif ($job[dbSyncDataJobs::field_restore_mode] == dbSyncDataJobs::mode_replace_all) {
	  		// alle Dateien ueberschreiben
	  		$replace_table = true;
	  	}
	  	elseif (!$table_exists) {
	  		// Tabelle existiert nicht
	  		$replace_table = true;
	  	}
	  	if ($replace_table) {
	  		$SQL_file = file($this->temp_path.'restore/'.$table['filename']);
	  		foreach ($SQL_file as $SQL) {
	  			$database->query($SQL);
	  			if ($database->is_error()) {
	  				// Fehler bei der Ruecksicherung
	  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
	  				$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
			  																	$job[dbSyncDataJobs::field_archive_number], 
			  																	$job_id, 
			  																	$this->getError(), 
			  																	$replace_table_name, 
			  																	filesize($this->temp_path.'restore/'.$table['filename']),
			  																	$table_exists ? dbSyncDataProtocol::action_mysql_replace : dbSyncDataProtocol::action_mysql_add,
			  																	dbSyncDataProtocol::status_error);
	  				return false;
	  			}
	  		}
	  		// erfolgreich wieder hergestellt
	  		$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
	  																	$job[dbSyncDataJobs::field_archive_number], 
	  																	$job_id, 
	  																	$table_exists ? sprintf(sync_protocol_table_replace, $replace_table_name) : sprintf(sync_protocol_table_add, $replace_table_name), 
	  																	$replace_table_name, 
	  																	filesize($this->temp_path.'restore/'.$table['filename']),
	  																	$table_exists ? dbSyncDataProtocol::action_mysql_replace : dbSyncDataProtocol::action_mysql_add,
	  																	dbSyncDataProtocol::status_ok);	
	  	}
  	}
		// Restore abgeschlossen
		return dbSyncDataJobs::status_finished;
	} // restoreTables()
  
	/**
	 * Restore files as specified
	 * 
	 * @param INT $job_id
	 * @return boolean|number
	 */
	private function restoreFiles($job_id) {
		global $kitTools;
		global $dbSyncDataJob;
		global $dbSyncDataProtocol; 
		global $dbSyncDataCfg;
		
		$filemtime_differ = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgFileMTimeDiffAllowed);
		
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
		
		// existiert die Dateiliste im /temp Verzeichnis?
  	if (false === ($list = unserialize(file_get_contents($this->temp_path.self::archive_list)))) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_read, $this->temp_path.self::archive_list)));
  		return false;
  	}
  	$restore_info = $this->array_search($list, 'filename', 'files/', true);
  	if (count($restore_info) < 1) {
  		// keine Dateien gefunden!
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sync_error_file_list_no_files));
  		return false;
  	}

  	// existiert die sync_data.ini im /temp Verzeichnis?
  	if (!file_exists($this->temp_path.self::sync_data_ini)) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_not_exists, self::sync_data_ini)));
  		return false;
  	}
  	// sync_data.ini auslesen
  	if (false === ($ini_data = parse_ini_file($this->temp_path.self::sync_data_ini, true))) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_read, str_replace(WB_PATH, '', $this->temp_path.self::sync_data_ini))));
  		return false;
  	}
 	
  	// ZIP initialisieren
  	$zip = new PclZip(WB_PATH.$job[dbSyncDataJobs::field_archive_file]);
  	
  	$running = (empty($job[dbSyncDataJobs::field_next_file])) ? true : false;
		
  	$ig_dir = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgIgnoreDirectories);
		$ig_dir = explode("\n", $ig_dir);
		$ignore_directories = array();
		foreach ($ig_dir as $id) $ignore_directories[] = WB_PATH.$id;
		$ignore_extensions = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgIgnoreFileExtensions);
		
		if ($running && ($job[dbSyncDataJobs::field_delete_files] == 1)) {
			// check if files should be deleted
			$files = $this->directoryTree(WB_PATH);
			$check_files = array();
			foreach ($restore_info as $file) {
				if (strpos($file['filename'], 'files/') != 0) continue;
				$check_files[] = substr($file['filename'], strlen('files/'));
			}
			foreach ($files as $file) {
				$filename = substr($file, strlen(WB_PATH)+1);
				foreach ($ignore_directories as $ig_dir) {
					if (strpos(WB_PATH.'/'.$filename, $ig_dir) !== false) continue 2; // continue with the next file in the for loop!
				}		
				// pruefen ob es sich um eine ignorierte Dateiendung handelt
				$ext = pathinfo($filename, PATHINFO_EXTENSION);
				if (in_array($ext, $ignore_extensions)) continue; 
				if (!in_array($filename, $check_files)) {
					// delete file
					if (!unlink(WB_PATH.'/'.$filename)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_delete, $filename)));
	  				$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
			  																	$job[dbSyncDataJobs::field_archive_number], 
			  																	$job_id, 
			  																	$this->getError(), 
			  																	$filename, 
			  																	0,
			  																	dbSyncDataProtocol::action_file_delete,
			  																	dbSyncDataProtocol::status_error);
	  				return false;
					}
					$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
			  																	$job[dbSyncDataJobs::field_archive_number], 
			  																	$job_id, 
			  																	sprintf(sync_protocol_file_delete, $filename), 
			  																	$filename, 
			  																	0,
			  																	dbSyncDataProtocol::action_file_delete,
			  																	dbSyncDataProtocol::status_ok);
				}					
			}
		} // check files for delete
		
		if ($running && isset($ini_data[self::section_deleted_files])) {
			// check for files which should be deleted by sync_data.ini command
			foreach ($ini_data[self::section_deleted_files] as $file) {
				$delete_file = WB_PATH.$file;
				if (file_exists($delete_file)) {
					// delete file
					if (!unlink($delete_file)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_file_delete, $file)));
	  				$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
			  																	$job[dbSyncDataJobs::field_archive_number], 
			  																	$job_id, 
			  																	$this->getError(), 
			  																	$file, 
			  																	0,
			  																	dbSyncDataProtocol::action_file_delete,
			  																	dbSyncDataProtocol::status_error);
	  				return false;
					}
					$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
			  																	$job[dbSyncDataJobs::field_archive_number], 
			  																	$job_id, 
			  																	sprintf(sync_protocol_file_delete, $file), 
			  																	$file, 
			  																	0,
			  																	dbSyncDataProtocol::action_file_delete,
			  																	dbSyncDataProtocol::status_ok);
				}
			}
		}
		
  	// Dateiliste durchlaufen
  	foreach ($restore_info as $file) {
  		// grant that the file is really from files/ directory within the ZIP archive and not any subdirectory
  		if (strpos($file['filename'], 'files/') != 0) continue;
  		$filename = substr($file['filename'], strlen('files/'));
  		// Datei festhalten
  		$this->next_file = $filename;
			$ex_time = (int) (microtime(true) - $this->getScriptStart());
			if ($ex_time >= $this->limit_execution_time) {
				// Zeitueberschreitung, Abbruch
				$this->status = dbSyncDataJobs::status_time_out; 
				return false;
			}
			// Bei Fortsetzung den Anfang suchen...
			if (!$running && ($filename != $job[dbSyncDataJobs::field_next_file])) {
				continue;
			}
      else {
				$running = true;
			}
			// directory to ignore?
			foreach ($ignore_directories as $ig_dir) {
 				if (strpos(WB_PATH.'/'.$filename, $ig_dir) !== false) continue 2; // continue with the next file in the for loop!
			}		
			
			// pruefen ob es sich um eine ignorierte Dateiendung handelt
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			if (in_array($ext, $ignore_extensions)) continue; 
			
			// ignore config.php?
			if (($filename == 'config.php') && ($job[dbSyncDataJobs::field_ignore_config] == 1)) continue;
			
			// ignore .htaccess?
			if (($filename == '.htaccess') && ($job[dbSyncDataJobs::field_ignore_htaccess] == 1)) continue;
			
			if (($job[dbSyncDataJobs::field_restore_mode] == dbSyncDataJobs::mode_replace_all) || !file_exists(WB_PATH.'/'.$filename)) {
				// file does not exists or all files should be overwritten
				if (false ===($list = $zip->extractByIndex($file['index'], PCLZIP_OPT_ADD_PATH, WB_PATH.'/', PCLZIP_OPT_REMOVE_PATH, 'files/', PCLZIP_OPT_REPLACE_NEWER))) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $zip->errorInfo(true)));
					$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
			  																$job[dbSyncDataJobs::field_archive_number], 
			  																$job_id, 
			  																$this->getError(), 
			  																$filename, 
			  																$file['size'],
			  																($job[dbSyncDataJobs::field_restore_mode] == dbSyncDataJobs::mode_replace_all) ? dbSyncDataProtocol::action_file_replace : dbSyncDataProtocol::action_file_add,
			  																dbSyncDataProtocol::status_error);
					return false;
				}
				// success
	  		$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
	  																	$job[dbSyncDataJobs::field_archive_number], 
	  																	$job_id, 
	  																	($job[dbSyncDataJobs::field_restore_mode] == dbSyncDataJobs::mode_replace_all) ? sprintf(sync_protocol_file_replace, $filename) : sprintf(sync_protocol_file_add, $filename), 
	  																	$filename, 
	  																	$file['size'],
	  																	($job[dbSyncDataJobs::field_restore_mode] == dbSyncDataJobs::mode_replace_all) ? dbSyncDataProtocol::action_file_replace : dbSyncDataProtocol::action_file_add,
			  															dbSyncDataProtocol::status_ok);	
			}
			elseif ($job[dbSyncDataJobs::field_restore_mode] == dbSyncDataJobs::mode_changed_date_size) {
				$t1 = filemtime(WB_PATH.'/'.$filename);
				$check = true;
				if ($t1 != $file['mtime']) {
					$check = false;
					if ($filemtime_differ > 0) {
						// allow different between the two values
						if (($t1 < $file['mtime']) && (($t1+$filemtime_differ) == $file['mtime'])) $check = true;
						if (($t1 > $file['mtime']) && (($t1-$filemtime_differ) == $file['mtime'])) $check = true;
					}
				}
				if (filesize(WB_PATH.'/'.$filename) != $file['size']) $check = false; 
				if (!$check) {
					if (false ===($list = $zip->extractByIndex($file['index'], PCLZIP_OPT_ADD_PATH, WB_PATH.'/', PCLZIP_OPT_REMOVE_PATH, 'files/', PCLZIP_OPT_REPLACE_NEWER))) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $zip->errorInfo(true)));
						$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
				  																$job[dbSyncDataJobs::field_archive_number], 
				  																$job_id, 
				  																$this->getError(), 
				  																$filename, 
				  																$file['size'],
				  																dbSyncDataProtocol::action_file_replace,
				  																dbSyncDataProtocol::status_error);
						return false;
					}
					// success
					$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
		  																	$job[dbSyncDataJobs::field_archive_number], 
		  																	$job_id, 
		  																	sprintf(sync_protocol_file_replace, $filename), 
		  																	$filename, 
		  																	$file['size'],
		  																	dbSyncDataProtocol::action_file_replace,
				  															dbSyncDataProtocol::status_ok);
				}
			}
			elseif ($job[dbSyncDataJobs::field_restore_mode] == dbSyncDataJobs::mode_changed_binary) {
				// binary file comparison
				if (false ===($list = $zip->extractByIndex($file['index'], PCLZIP_OPT_ADD_PATH, $this->temp_path, PCLZIP_OPT_REMOVE_PATH, 'files/'))) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $zip->errorInfo(true)));
					$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
			  																$job[dbSyncDataJobs::field_archive_number], 
			  																$job_id, 
			  																$this->getError(), 
			  																$filename, 
			  																$file['size'],
			  																dbSyncDataProtocol::action_file_compare,
			  																dbSyncDataProtocol::status_error);
					return false;
				}
				if (!$this->compare_files($this->temp_path.$filename, WB_PATH.'/'.$filename)) {
					// files differ
					if (false ===($list = $zip->extractByIndex($file['index'], PCLZIP_OPT_ADD_PATH, WB_PATH.'/', PCLZIP_OPT_REMOVE_PATH, 'files/', PCLZIP_OPT_REPLACE_NEWER))) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $zip->errorInfo(true)));
						$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
				  																$job[dbSyncDataJobs::field_archive_number], 
				  																$job_id, 
				  																$this->getError(), 
				  																$filename, 
				  																$file['size'],
				  																dbSyncDataProtocol::action_file_replace,
				  																dbSyncDataProtocol::status_error);
				  	@unlink($this->temp_path.$filename);
						return false;
					}
					// success
					$dbSyncDataProtocol->addEntry($job[dbSyncDataJobs::field_archive_id], 
	  																		$job[dbSyncDataJobs::field_archive_number], 
	  																		$job_id, 
	  																		sprintf(sync_protocol_file_replace, $filename), 
	  																		$filename, 
	  																		$file['size'],
	  																		dbSyncDataProtocol::action_file_replace,
	  																		dbSyncDataProtocol::status_ok);
	  			@unlink($this->temp_path.$filename);
				}
			}
  	} // foreach
  	
		return dbSyncDataJobs::status_finished;
	} // restoreFiles()
	
	/**
	 * Interrupt restoring process
	 * 
	 * @param INT $job_id
	 * @param INT $archive_id
	 * @param BOOL $process_files
	 * @param FLOAT $old_total_time
	 * @return INT $status
	 */
	private function restoreInterrupt($job_id, $archive_id, $process_files=true, $old_total_time=0) {
		global $dbSyncDataJob;
		
		$data = array(
			dbSyncDataJobs::field_status				=> dbSyncDataJobs::status_time_out,
			dbSyncDataJobs::field_last_message	=> 'TIME_OUT',
			dbSyncDataJobs::field_total_time		=> (microtime(true) - $this->getScriptStart())+$old_total_time,
			dbSyncDataJobs::field_next_action		=> ($process_files) ? dbSyncDataJobs::next_action_file : dbSyncDataJobs::next_action_mysql,
			dbSyncDataJobs::field_next_file			=> $this->next_file
		);
		$where = array(
			dbSyncDataJobs::field_id	=> $job_id
		);
		if (!$dbSyncDataJob->sqlUpdateRecord($data, $where)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		// Status zurueckgeben	
		return dbSyncDataJobs::status_time_out;
	} // restoreInterrupt()
	
	/**
	 * Restoring process is finished
	 * 
	 * @param INT $job_id
	 * @param INT $archive_id
	 * @param FLOAT $old_total_time
	 * @return INT $status
	 */
	private function restoreFinish($job_id, $archive_id, $old_total_time) {
		global $dbSyncDataJob;
		
		$data = array(
			dbSyncDataJobs::field_status				=> dbSyncDataJobs::status_finished,
			dbSyncDataJobs::field_last_message	=> 'FINISHED',
			dbSyncDataJobs::field_total_time		=> (microtime(true) - $this->getScriptStart())+$old_total_time,
			dbSyncDataJobs::field_next_action		=> dbSyncDataJobs::next_action_none,
			dbSyncDataJobs::field_next_file			=> '',
			dbSyncDataJobs::field_end						=> date('Y-m-d H:i:s')
		);
		$where = array(
			dbSyncDataJobs::field_id	=> $job_id
		);
		if (!$dbSyncDataJob->sqlUpdateRecord($data, $where)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		
		// temporaeres Verzeichnis aufraeumen
		if (!$this->clearDirectory($this->temp_path, true) && $this->isError()) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
			return false;
		}
		
		// Status zurueckgeben	
		return dbSyncDataJobs::status_finished;
	}	// restoreFinish()
	
	/**
	 * Start the process of updating the specified archive
	 * 
	 * @param STR $archive_id
	 * @param STR $update_name - name to use for the update archive file
	 * @param REFERENCE INT $job_id
	 * @return INT|BOOL integer value of the next action or FALSE on error
	 */
	public function updateStart($archive_id, $update_name, &$job_id) {
		global $dbSyncDataJob;
		global $dbSyncDataArchive;
		
		// get the highest archive number
		$SQL = sprintf(	"SELECT * FROM %s WHERE %s='%s' AND %s='%s' ORDER BY %s DESC LIMIT 1",
										$dbSyncDataArchive->getTableName(),
										dbSyncDataArchives::field_archive_id,
										$archive_id,
										dbSyncDataArchives::field_status,
										dbSyncDataArchives::status_active,
										dbSyncDataArchives::field_archive_number);
		$old_archive = array();
		if (!$dbSyncDataArchive->sqlExec($SQL, $old_archive)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataArchive->getError()));
			return false;
		}
		if (count($old_archive) < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_archive_id_invalid, $archive_id)));
			return false;
		}
		$old_archive = $old_archive[0];
		
		$new_archive_number = $old_archive[dbSyncDataArchives::field_archive_number]+1;

		// create a new archive for the update
		$data = array(
			dbSyncDataArchives::field_archive_date		=> date('Y-m-d H:i:s'),
			dbSyncDataArchives::field_archive_id			=> $archive_id,
			dbSyncDataArchives::field_archive_name		=> (!empty($update_name)) ? $update_name : sprintf(sync_str_update_default_name, date(sync_cfg_datetime_str)),
			dbSyncDataArchives::field_archive_number	=> $new_archive_number,
			dbSyncDataArchives::field_archive_type		=> $old_archive[dbSyncDataArchives::field_archive_type],
			dbSyncDataArchives::field_status					=> dbSyncDataArchives::status_active
		);
		$id = -1;
		if (!$dbSyncDataArchive->sqlInsertRecord($data, $id)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataArchive->getError()));
			return false;
		}
		
		// Job anlegen
		$archive_file = sprintf('%s-%03d.zip', date('Ymd-His', strtotime($data[dbSyncDataArchives::field_archive_date])), $new_archive_number);
		
		$job_type = dbSyncDataJobs::type_undefined;
		switch ($old_archive[dbSyncDataArchives::field_archive_type]):
		case dbSyncDataArchives::backup_type_complete:
			$job_type = dbSyncDataJobs::type_backup_complete; break;
		case dbSyncDataArchives::backup_type_files:
			$job_type = dbSyncDataJobs::type_backup_files; break;
		case dbSyncDataArchives::backup_type_mysql:
			$job_type = dbSyncDataJobs::type_backup_mysql; break;
		endswitch;
		
		$data = array(
			dbSyncDataJobs::field_archive_file		=> $archive_file,
			dbSyncDataJobs::field_archive_number	=> $new_archive_number,
			dbSyncDataJobs::field_archive_id			=> $archive_id,
			dbSyncDataJobs::field_errors					=> 0,
			dbSyncDataJobs::field_start						=> date('Y-m-d H:i:s'),
			dbSyncDataJobs::field_status					=> dbSyncDataJobs::status_start,
			dbSyncDataJobs::field_type						=> $job_type,
			dbSyncDataJobs::field_next_file				=> ''
		);
		$job_id = -1;
		if (!$dbSyncDataJob->sqlInsertRecord($data, $job_id)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		
		// ggf. bereits existierendes ZIP Archiv loeschen
		$zip_file = $this->temp_path.$archive_file;
		if (file_exists($zip_file)) unlink($zip_file);
		
		if (($job_type == dbSyncDataJobs::type_backup_complete) || ($job_type == dbSyncDataJobs::type_backup_mysql)) {
			// MySQL Sicherung
			if (!$this->updateTables($job_id)) {
				// Fehler oder Timeout beim Sichern der Tabellen
				if ($this->isError()) return false;
				if ($this->status == dbSyncDataJobs::status_time_out) { 
					// Operation vorzeitig beenden...
					return $this->updateInterrupt($job_id, $archive_id, false, 0);
				}
			}
		}

		if (($job_type == dbSyncDataJobs::type_backup_complete) || ($job_type == dbSyncDataJobs::type_backup_files)) {
			// update files
			if (!$this->updateFiles($job_id)) {
				// Fehler oder Timeout beim Sichern der Dateien
				if ($this->isError()) return false;
				if ($this->status == dbSyncDataJobs::status_time_out) { 
					// Operation vorzeitig beenden...
					return $this->updateInterrupt($job_id, $archive_id, true, 0);
				}
			}
		}
		return $this->updateFinish($job_id, $archive_id, 0);
	} // updateStart()
	
	/**
	 * Finish the update process, write the archive file and 
	 * gather some informations
	 * 
	 * @param INT $job_id
	 * @param STR $archive_id
	 * @param FLOAT $old_total_time
	 * @return INT|BOOL integer value of the action or FALSE on error
	 */
	private function updateFinish($job_id, $archive_id, $old_total_time) {
		global $dbSyncDataJob;
		global $dbSyncDataFile;
		global $dbSyncDataArchive;
		global $kitTools;
		
		// Datensicherung abgeschlossen
		$data = array(
			dbSyncDataJobs::field_status				=> dbSyncDataJobs::status_finished,
			dbSyncDataJobs::field_last_message	=> 'FINISHED',
			dbSyncDataJobs::field_total_time		=> (microtime(true) - $this->script_start) + $old_total_time,
			dbSyncDataJobs::field_next_action		=> dbSyncDataJobs::next_action_none,
			dbSyncDataJobs::field_next_file			=> '',
			dbSyncDataJobs::field_end						=> date('Y-m-d H:i:s')
		);
		$where = array(
			dbSyncDataJobs::field_id	=> $job_id
		);
		if (!$dbSyncDataJob->sqlUpdateRecord($data, $where)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		$job = array();
		if (!$dbSyncDataJob->sqlSelectRecord($where, $job)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		$job = $job[0];
		
		$where = array(	dbSyncDataArchives::field_archive_id 			=> $job[dbSyncDataJobs::field_archive_id],
										dbSyncDataArchives::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number]);
		$archive = array();
		if (!$dbSyncDataArchive->sqlSelectRecord($where, $archive)) {
			$this->setError(sprintf('[%s _ %s] %s', __METHOD__, __LINE__, $dbSyncDataArchive->getError()));
			return false;
		}
		if (count($archive) < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_archive_id_invalid, $job[dbSyncDataJobs::field_archive_id])));
			return false;
		}
		$archive = $archive[0];
		
		$zip_file = $this->temp_path.$job[dbSyncDataJobs::field_archive_file];
		$zip = new PclZip($zip_file);
		
		// Dateiliste zum Archiv hinzufuegen
		if (($job[dbSyncDataJobs::field_type] == dbSyncDataJobs::type_backup_complete) ||
				($job[dbSyncDataJobs::field_type] == dbSyncDataJobs::type_backup_files)) {
			$list = array();
			if (0 == ($list = $zip->add($this->temp_path.self::file_list, '', $this->temp_path))) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $zip->errorInfo(true)));
				return false;
			}
		}
		
		// Anzahl und Umfang der gesicherten Dateien ermitteln
		$SQL = sprintf(	"SELECT COUNT(%s) as files_count, SUM(%s) as files_bytes FROM %s WHERE %s='%s' AND %s='%s' AND %s='%s' AND (%s='%s' OR %s='%s')",
										dbSyncDataFiles::field_file_name,
										dbSyncDataFiles::field_file_size,
										$dbSyncDataFile->getTableName(),
										dbSyncDataFiles::field_archive_id,
										$archive_id, 
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
		
		// get deleted tables
		$SQL = sprintf(	"SELECT %s FROM %s WHERE %s='%s' AND %s='%s' AND %s='%s' AND %s='%s' AND %s='%s'",
										dbSyncDataFiles::field_file_name,
										$dbSyncDataFile->getTableName(),
										dbSyncDataFiles::field_archive_id,
										$archive_id,
										dbSyncDataFiles::field_archive_number,
										$job[dbSyncDataJobs::field_archive_number],
										dbSyncDataFiles::field_status,
										dbSyncDataFiles::status_ok,
										dbSyncDataFiles::field_action,
										dbSyncDataFiles::action_delete,
										dbSyncDataFiles::field_file_type,
										dbSyncDataFiles::file_type_mysql);
		$result = array();
		if (!$dbSyncDataFile->sqlExec($SQL, $result)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
			return false;
		}
		$mysql_deleted = array();
		foreach ($result as $table) {
			$mysql_deleted[] = $table[dbSyncDataFiles::field_file_name];
		}

		// get deleted files
		$SQL = sprintf(	"SELECT %s FROM %s WHERE %s='%s' AND %s='%s' AND %s='%s' AND %s='%s' AND %s='%s'",
										dbSyncDataFiles::field_file_path,
										$dbSyncDataFile->getTableName(),
										dbSyncDataFiles::field_archive_id,
										$archive_id,
										dbSyncDataFiles::field_archive_number,
										$job[dbSyncDataJobs::field_archive_number],
										dbSyncDataFiles::field_status,
										dbSyncDataFiles::status_ok,
										dbSyncDataFiles::field_action,
										dbSyncDataFiles::action_delete,
										dbSyncDataFiles::field_file_type,
										dbSyncDataFiles::file_type_file);
		$result = array();
		if (!$dbSyncDataFile->sqlExec($SQL, $result)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
			return false;
		}
		$files_deleted = array();
		foreach ($result as $file) {
			$files_deleted[] = $file[dbSyncDataFiles::field_file_path];
		}
		
		// Informationen ueber das Update in der sync_data.ini festhalten
		$ini = $job;
		$ini[self::used_wb_path] = WB_PATH;
		$ini[self::used_wb_url] = WB_URL;
		$ini[self::used_table_prefix] = TABLE_PREFIX;
		$ini[self::total_files] = $files[0]['files_count'];
		$ini[self::total_size] = $files[0]['files_bytes'];
		
		$ini_sections = array(
			self::section_general					=> $ini,
			self::section_deleted_tables	=> $mysql_deleted,
			self::section_deleted_files		=> $files_deleted
		);
		
		if (!$this->write_ini_file($ini_sections, $this->temp_path.self::sync_data_ini, true)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
			return false;
		}
		
		// sync_data.ini in das Archiv aufnehmen
		if (0 == ($list = $zip->add($this->temp_path.self::sync_data_ini, '', $this->temp_path))) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $zip->errorInfo(true)));
			return false;
		}
		
		if (!file_exists($this->backup_path)) {
			if (!mkdir($this->backup_path, 0755, true)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_mkdir, $this->backup_path)));
				return false;
			}
		}
		$archive_name = (!empty($archive[dbSyncDataArchives::field_archive_name])) ? page_filename($archive[dbSyncDataArchives::field_archive_name].'.zip') : $job[dbSyncDataJobs::field_archive_file];
		
		if (!copy($this->temp_path.$job[dbSyncDataJobs::field_archive_file], $this->backup_path.$archive_name)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_copy_file, $this->temp_path.$job[dbSyncDataJobs::field_archive_file], $this->backup_path.$archive_name)));
			return false;
		}
		
		// temporaeres Verzeichnis aufraeumen
		if (!$this->clearDirectory($this->temp_path, true) && $this->isError()) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
			return false;
		}
		// return status
		return dbSyncDataJobs::status_finished;
	} // updateFinish()
	
	/**
	 * Update all MySQL data tables
	 * 
	 * @param STR $job_id
	 * @return BOOL
	 */
	private function updateTables($job_id) {
		global $dbSyncDataJob;
		global $dbSyncDataArchive;
		global $dbSyncDataFile;
		global $dbSyncDataCfg;
		
		$where = array(dbSyncDataJobs::field_id => $job_id);
		$job = array();
		if (!$dbSyncDataJob->sqlSelectRecord($where, $job)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		$job = $job[0];
		
		$running = (empty($job[dbSyncDataJobs::field_next_file])) ? true : false;
		
		$tables = array();
		if (false === ($tables = $this->getTables())) {
			return false;
		}
		$zip_file = $this->temp_path.$job[dbSyncDataJobs::field_archive_file];
		$zip = new PclZip($zip_file);
		
		$this->status = dbSyncDataJobs::status_undefined; 
		
		// zu ignorierende Tabellen in ein Array schreiben
		$ig_tab = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgIgnoreTables);
		$ig_tab = explode("\n", $ig_tab);
		$ignore_tables = array();
		foreach ($ig_tab as $it) $ignore_tables[] = TABLE_PREFIX.$it;
		
		// get tables from archive
		$SQL = sprintf(	"SELECT * FROM %s WHERE %s='%s' AND %s='%s' AND %s='%s' AND (%s='%s' OR %s='%s')",
										$dbSyncDataFile->getTableName(),
										dbSyncDataFiles::field_archive_id,
										$job[dbSyncDataJobs::field_archive_id],
										dbSyncDataFiles::field_file_type,
										dbSyncDataFiles::file_type_mysql,
										dbSyncDataFiles::field_status,
										dbSyncDataFiles::status_ok,
										dbSyncDataFiles::field_action,
										dbSyncDataFiles::action_add,
										dbSyncDataFiles::field_action,
										dbSyncDataFiles::action_replace);
		$aTables = array();
		if (!$dbSyncDataFile->sqlExec($SQL, $aTables)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
			return false;
		}
		// build array with already archived tables
		$archived_tables = array();
		foreach ($aTables as $aTable) {
			$archived_tables[] = substr($aTable[dbSyncDataFiles::field_file_name], 0, strpos($aTable[dbSyncDataFiles::field_file_name], '.sql'));		
		}
		
		if ($running) {
			// check for deleted tables - process only at start ($running == true), must be done within script execution time			
			foreach ($archived_tables as $archived_table) {
				if (!in_array($archived_table, $tables)) {
					// table is deleted
					$data = array(
						dbSyncDataFiles::field_action					=> dbSyncDataFiles::action_delete,
						dbSyncDataFiles::field_archive_id			=> $job[dbSyncDataFiles::field_archive_id],
						dbSyncDataFiles::field_archive_number	=> $job[dbSyncDataFiles::field_archive_number],
						dbSyncDataFiles::field_error_msg			=> '',
						dbSyncDataFiles::field_file_checksum	=> 0,
						dbSyncDataFiles::field_file_date			=> '0000-00-00 00:00:00',
						dbSyncDataFiles::field_file_name			=> $archived_table.'.sql',
						dbSyncDataFiles::field_file_path			=> '',
						dbSyncDataFiles::field_file_size			=> 0,
						dbSyncDataFiles::field_file_type			=> dbSyncDataFiles::file_type_mysql,
						dbSyncDataFiles::field_status					=> dbSyncDataFiles::status_ok
					);
					if (!$dbSyncDataFile->sqlInsertRecord($data)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
						return false;
					}
				}
			}
		}
		
		// walk through tables and check if they are changed
		foreach ($tables as $table) {
			$this->next_file = $table;
			$ex_time = (int) (microtime(true) - $this->getScriptStart());
			if ($ex_time >= $this->limit_execution_time) {
				$this->status = dbSyncDataJobs::status_time_out; 
				return false;
			}
			// Bei Fortsetzung den Anfang suchen...
			if (!$running && ($table != $job[dbSyncDataJobs::field_next_file])) {
				continue;
			}
      else {
				$running = true;
			}			
			$data = array();
			if (in_array($table, $ignore_tables)) {
				// Tabelle ignorieren
				$data = array(
					dbSyncDataFiles::field_action					=> dbSyncDataFiles::action_ignore,
					dbSyncDataFiles::field_archive_id			=> $job[dbSyncDataJobs::field_archive_id],
					dbSyncDataFiles::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number],
					dbSyncDataFiles::field_file_checksum	=> 0,
					dbSyncDataFiles::field_file_date			=> '0000-00-00 00:00:00',
					dbSyncDataFiles::field_file_name			=> $table.'.sql',
					dbSyncDataFiles::field_file_path			=> '',
					dbSyncDataFiles::field_file_type			=> dbSyncDataFiles::file_type_mysql,
					dbSyncDataFiles::field_file_size			=> 0,
					dbSyncDataFiles::field_status					=> dbSyncDataFiles::status_ok,
					dbSyncDataFiles::field_error_msg			=> ''
				);
			}
			elseif (!in_array($table, $archived_tables)) {
				// table does not exists in former archive, still add it
				$list = array();
				if (0 == ($list = $zip->add($this->temp_path.'sql/'.$table.'.sql', PCLZIP_OPT_ADD_PATH, 'sql', PCLZIP_OPT_REMOVE_PATH, $this->temp_path.'sql/'))) {
					// Fehler beim Hinzufuegen der Datei
					$data = array(
						dbSyncDataFiles::field_action					=> dbSyncDataFiles::action_add,
						dbSyncDataFiles::field_archive_id			=> $job[dbSyncDataJobs::field_archive_id],
						dbSyncDataFiles::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number],
						dbSyncDataFiles::field_file_checksum	=> 0,
						dbSyncDataFiles::field_file_date			=> '0000-00-00 00:00:00',
						dbSyncDataFiles::field_file_name			=> $table.'.sql',
						dbSyncDataFiles::field_file_path			=> '',
						dbSyncDataFiles::field_file_type			=> dbSyncDataFiles::file_type_mysql,
						dbSyncDataFiles::field_file_size			=> 0,
						dbSyncDataFiles::field_status					=> dbSyncDataFiles::status_error,
						dbSyncDataFiles::field_error_msg			=> $zip->errorInfo(true)
					);
				}
				else {
					$data = array(
						dbSyncDataFiles::field_action					=> dbSyncDataFiles::action_add,
						dbSyncDataFiles::field_archive_id			=> $job[dbSyncDataJobs::field_archive_id],
						dbSyncDataFiles::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number],
						dbSyncDataFiles::field_file_checksum	=> dechex($list[0]['crc']),
						dbSyncDataFiles::field_file_date			=> date('Y-m-d H:i:s', $list[0]['mtime']),
						dbSyncDataFiles::field_file_name			=> $table.'.sql',
						dbSyncDataFiles::field_file_path			=> '',
						dbSyncDataFiles::field_file_type			=> dbSyncDataFiles::file_type_mysql,
						dbSyncDataFiles::field_file_size			=> $list[0]['size'],
						dbSyncDataFiles::field_status					=> dbSyncDataFiles::status_ok,
						dbSyncDataFiles::field_error_msg			=> ''
					);
				}
				if (!$dbSyncDataFile->sqlInsertRecord($data)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
					return false;
				}
			}
			else {
				// check table before doing anything
				$update = false;
				// get the archived table from array
				foreach ($aTables as $aTable) {
					$cmp = substr($aTable[dbSyncDataFiles::field_file_name], 0, strpos($aTable[dbSyncDataFiles::field_file_name], '.sql'));
					if ($cmp == $table) {
						// start comparison
						$this->saveTable($table);
						$crc_temp = dechex(crc32(file_get_contents($this->temp_path.'sql/'.$table.'.sql')));
						if ($crc_temp != $aTable[dbSyncDataFiles::field_file_checksum]) $update = true;
						break;
					}
				}
				if ($update) {
					$list = array();
					if (0 == ($list = $zip->add($this->temp_path.'sql/'.$table.'.sql', PCLZIP_OPT_ADD_PATH, 'sql', PCLZIP_OPT_REMOVE_PATH, $this->temp_path.'sql/'))) {
						// Fehler beim Hinzufuegen der Datei
						$data = array(
							dbSyncDataFiles::field_action					=> dbSyncDataFiles::action_add,
							dbSyncDataFiles::field_archive_id			=> $job[dbSyncDataJobs::field_archive_id],
							dbSyncDataFiles::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number],
							dbSyncDataFiles::field_file_checksum	=> 0,
							dbSyncDataFiles::field_file_date			=> '0000-00-00 00:00:00',
							dbSyncDataFiles::field_file_name			=> $table.'.sql',
							dbSyncDataFiles::field_file_path			=> '',
							dbSyncDataFiles::field_file_type			=> dbSyncDataFiles::file_type_mysql,
							dbSyncDataFiles::field_file_size			=> 0,
							dbSyncDataFiles::field_status					=> dbSyncDataFiles::status_error,
							dbSyncDataFiles::field_error_msg			=> $zip->errorInfo(true)
						);
					}
					else {
						$data = array(
							dbSyncDataFiles::field_action					=> dbSyncDataFiles::action_replace,
							dbSyncDataFiles::field_archive_id			=> $job[dbSyncDataJobs::field_archive_id],
							dbSyncDataFiles::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number],
							dbSyncDataFiles::field_file_checksum	=> dechex($list[0]['crc']),
							dbSyncDataFiles::field_file_date			=> date('Y-m-d H:i:s', $list[0]['mtime']),
							dbSyncDataFiles::field_file_name			=> $table.'.sql',
							dbSyncDataFiles::field_file_path			=> '',
							dbSyncDataFiles::field_file_type			=> dbSyncDataFiles::file_type_mysql,
							dbSyncDataFiles::field_file_size			=> $list[0]['size'],
							dbSyncDataFiles::field_status					=> dbSyncDataFiles::status_ok,
							dbSyncDataFiles::field_error_msg			=> ''
						);
					}
					if (!$dbSyncDataFile->sqlInsertRecord($data)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
						return false;
					}
				}				
			}
		}
		return true;
	} // updateTables()
	
	/**
	 * Interrupt the update process due exceeding time limit, save the data 
	 * and return to caller
	 * 
	 * @param INT $job_id
	 * @param VARCHAR $archive_id
	 * @param BOOL $process_files
	 * @param FLOAT $old_total_time
	 * @return INT|BOOL integer value of status or FALSE on error
	 */
	private function updateInterrupt($job_id, $archive_id, $process_files, $old_total_time) {
		global $dbSyncDataJob;
		
		$data = array(
			dbSyncDataJobs::field_status				=> dbSyncDataJobs::status_time_out,
			dbSyncDataJobs::field_last_message	=> 'TIME_OUT',
			dbSyncDataJobs::field_total_time		=> (microtime(true) - $this->getScriptStart())+$old_total_time,
			dbSyncDataJobs::field_next_action		=> ($process_files) ? dbSyncDataJobs::next_action_file : dbSyncDataJobs::next_action_mysql,
			dbSyncDataJobs::field_next_file			=> $this->next_file
		);
		$where = array(
			dbSyncDataJobs::field_id	=> $job_id
		);
		if (!$dbSyncDataJob->sqlUpdateRecord($data, $where)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		// Status zurueckgeben	
		return dbSyncDataJobs::status_time_out;
	} // updateInterrupt()
	
	/**
	 * Updates all files within the update process
	 * 
	 * @param INT $job_id
	 * @return INT|BOOL integer value of next action or FALSE on error
	 */
	private function updateFiles($job_id) {
		global $dbSyncDataJob;
		global $dbSyncDataArchive;
		global $dbSyncDataFile;
		global $dbSyncDataCfg;
		
		$where = array(dbSyncDataJobs::field_id => $job_id);
		$job = array();
		if (!$dbSyncDataJob->sqlSelectRecord($where, $job)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
			return false;
		}
		$job = $job[0];
		
		$running = (empty($job[dbSyncDataJobs::field_next_file])) ? true : false;
		
		$zip_file = $this->temp_path.$job[dbSyncDataJobs::field_archive_file];
		$zip = new PclZip($zip_file);
		
		$this->status = dbSyncDataJobs::status_undefined; 
		
		if ($running) {
			// on first call create file_list and store it at /temp directory
			$files = $this->directoryTree(WB_PATH);
			$fp = fopen($this->temp_path.self::file_list, 'w');
			foreach($files as $file) fputs($fp, $file."\n");
			fclose($fp); 			
		}
		// get the actual file list
		$files = file($this->temp_path.self::file_list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$max = count($files);
		
		$ig_dir = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgIgnoreDirectories);
		$ig_dir = explode("\n", $ig_dir);
		$ignore_directories = array();
		foreach ($ig_dir as $id) $ignore_directories[] = WB_PATH.$id;
		$ignore_extensions = $dbSyncDataCfg->getValue(dbSyncDataCfg::cfgIgnoreFileExtensions);
		
		// get files from archive
		$SQL = sprintf(	"SELECT * FROM %s WHERE %s='%s' AND %s='%s' AND %s='%s' AND (%s='%s' OR %s='%s')",
										$dbSyncDataFile->getTableName(),
										dbSyncDataFiles::field_archive_id,
										$job[dbSyncDataJobs::field_archive_id],
										dbSyncDataFiles::field_file_type,
										dbSyncDataFiles::file_type_file,
										dbSyncDataFiles::field_status,
										dbSyncDataFiles::status_ok,
										dbSyncDataFiles::field_action,
										dbSyncDataFiles::action_add,
										dbSyncDataFiles::field_action,
										dbSyncDataFiles::action_replace);
		$aFiles = array();
		if (!$dbSyncDataFile->sqlExec($SQL, $aFiles)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
			return false;
		}
		// build array with already archived tables
		$archived_files = array();
		$i=0;
		foreach ($aFiles as $aFile) {
			$archived_files[$i] = $aFile[dbSyncDataFiles::field_file_path];
			$i++;		
		}
		
		if ($running) {
			// on first run check if there are files to delete
			foreach ($aFiles as $check) {
				if (!in_array(WB_PATH.$check[dbSyncDataFiles::field_file_path], $files)) {
					// file is deleted
					$data = array(
						dbSyncDataFiles::field_action					=> dbSyncDataFiles::action_delete,
						dbSyncDataFiles::field_archive_id			=> $job[dbSyncDataJobs::field_archive_id],
						dbSyncDataFiles::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number],
						dbSyncDataFiles::field_error_msg			=> '',
						dbSyncDataFiles::field_file_checksum	=> $check[dbSyncDataFiles::field_file_checksum],
						dbSyncDataFiles::field_file_date			=> $check[dbSyncDataFiles::field_file_date],
						dbSyncDataFiles::field_file_name			=> $check[dbSyncDataFiles::field_file_name],
						dbSyncDataFiles::field_file_path			=> $check[dbSyncDataFiles::field_file_path],
						dbSyncDataFiles::field_file_size			=> $check[dbSyncDataFiles::field_file_size],
						dbSyncDataFiles::field_file_type			=> dbSyncDataFiles::file_type_file,
						dbSyncDataFiles::field_status					=> dbSyncDataFiles::status_ok
					);
					if (!$dbSyncDataFile->sqlInsertRecord($data)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
						return false;
					}
				}
			}
		}
		
		foreach ($files as $file) {
			$this->next_file = $file; 
			$ex_time = (int) (microtime(true) - $this->getScriptStart());
			if ($ex_time >= $this->limit_execution_time) {
				$this->status = dbSyncDataJobs::status_time_out; 
				return false;
			}
			// leere Zeilen ueberspringen
			if (empty($file)) continue;
			// Verzeichnisse ueberspringen
			if (is_dir($file)) continue;
			// Bei Fortsetzung den Anfang suchen...
			if (!$running && ($file != $job[dbSyncDataJobs::field_next_file])) {
				continue;
			}
			else {
				$running = true;
			}
			
			foreach ($ignore_directories as $ig_dir) {
				if (strpos($file, $ig_dir) !== false) {
					continue 2; // continue with the next file in the for loop!	
				}
			}		
			
			// pruefen ob es sich um eine ignorierte Dateiendung handelt
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			if (in_array($ext, $ignore_extensions)) continue;
			
			$update = false;
			$action = dbSyncDataFiles::action_ignore;
			if (!in_array(substr($file, strlen(WB_PATH)), $archived_files)) {
				$update = true;
				$action = dbSyncDataFiles::action_add;
			}
			else {
				// compare file with archived file
				$i = array_search(substr($file, strlen(WB_PATH)), $archived_files);
				if (dechex(crc32(file_get_contents($file))) != $aFiles[$i][dbSyncDataFiles::field_file_checksum]) {
					$update = true;
					$action = dbSyncDataFiles::action_replace;
				}
			}
			if ($update) {
				// add new or changed file to archive
				$data = array();
	      $list = array();
	      if (0 == ($list = $zip->add($file, PCLZIP_OPT_ADD_PATH, 'files', PCLZIP_OPT_REMOVE_PATH, WB_PATH))) {
					// Fehler beim Hinzufuegen der Datei
					$data = array(
						dbSyncDataFiles::field_action					=> $action,
						dbSyncDataFiles::field_archive_id			=> $job[dbSyncDataJobs::field_archive_id],
						dbSyncDataFiles::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number],
						dbSyncDataFiles::field_file_checksum	=> 0,
						dbSyncDataFiles::field_file_date			=> '0000-00-00 00:00:00',
						dbSyncDataFiles::field_file_name			=> basename($file),
						dbSyncDataFiles::field_file_path			=> str_replace(WB_PATH, '', $file),
						dbSyncDataFiles::field_file_type			=> dbSyncDataFiles::file_type_file,
						dbSyncDataFiles::field_file_size			=> 0,
						dbSyncDataFiles::field_status					=> dbSyncDataFiles::status_error,
						dbSyncDataFiles::field_error_msg			=> $zip->errorInfo(true)
					); 
				}
				else {
					$data = array(
						dbSyncDataFiles::field_action					=> $action,
						dbSyncDataFiles::field_archive_id			=> $job[dbSyncDataJobs::field_archive_id],
						dbSyncDataFiles::field_archive_number	=> $job[dbSyncDataJobs::field_archive_number],
						dbSyncDataFiles::field_file_checksum	=> dechex($list[0]['crc']),
						dbSyncDataFiles::field_file_date			=> date('Y-m-d H:i:s', $list[0]['mtime']),
						dbSyncDataFiles::field_file_name			=> basename($file),
						dbSyncDataFiles::field_file_path			=> str_replace(WB_PATH, '', $file),
						dbSyncDataFiles::field_file_type			=> dbSyncDataFiles::file_type_file,
						dbSyncDataFiles::field_file_size			=> $list[0]['size'],
						dbSyncDataFiles::field_status					=> dbSyncDataFiles::status_ok,
						dbSyncDataFiles::field_error_msg			=> ''
					);
				}
				if (!$dbSyncDataFile->sqlInsertRecord($data)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataFile->getError()));
					return false;
				}
			}
		}
		
	} // updateFiles()
	
	/**
	 * Continue the update process
	 * 
	 * @param INT $job_id
	 * @return INT|BOOL integer value of next action or FALSE on error
	 */
	public function updateContinue($job_id) {
		global $dbSyncDataArchive;
		global $kitTools;
		global $dbSyncDataJob;
		global $dbSyncDataFile;
		
		if ($job_id < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(sync_error_job_id_invalid, $job_id)));
			return false;
		}
		
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
		
		if ($job[dbSyncDataJobs::field_status] == dbSyncDataJobs::status_time_out) {
			// Datensicherung fortsetzen
			$finished = false;
			if ($job[dbSyncDataJobs::field_next_action] == dbSyncDataJobs::next_action_mysql) {
				// Tabellen sichern
				if (!$this->updateTables($job_id)) {
					// Fehler oder Timeout beim Sichern der Dateien
					if ($this->isError()) return false;
					if ($this->status == dbSyncDataJobs::status_time_out) {
						// Operation vorzeitig beenden...
						return $this->updateInterrupt($job_id, $job[dbSyncDataJobs::field_archive_id], false, $job[dbSyncDataJobs::field_total_time]);
					}
				}
				elseif ($job[dbSyncDataJobs::field_type] == dbSyncDataJobs::type_backup_complete) {
					// MySQL Sicherung abgeschlossen
					$where = array(dbSyncDataJobs::field_id => $job_id);
					$data = array(
						dbSyncDataJobs::field_next_action => dbSyncDataJobs::next_action_file,
						dbSyncDataJobs::field_next_file => ''
					);
					if (!$dbSyncDataJob->sqlUpdateRecord($data, $where)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbSyncDataJob->getError()));
						return false;
					}
					if (!$this->updateFiles($job_id)) {
						// Fehler oder Timeout beim Sichern der Dateien
						if ($this->isError()) return false;
						if ($this->status == dbSyncDataJobs::status_time_out) {
							// Operation vorzeitig beenden...
							return $this->updateInterrupt($job_id, $job[dbSyncDataJobs::field_archive_id], true, $job[dbSyncDataJobs::field_total_time]);
						}
					}					
					$finished = true;
				}
				else {
					$finished = true;
				}
			}
			
			if ($job[dbSyncDataJobs::field_next_action] == dbSyncDataJobs::next_action_file) {
				// Dateien sichern
				if (!$this->updateFiles($job_id)) {
					// Fehler oder Timeout beim Sichern der Dateien
					if ($this->isError()) return false;
					if ($this->status == dbSyncDataJobs::status_time_out) { 
						// Operation vorzeitig beenden...
						return $this->updateInterrupt($job_id, $job[dbSyncDataJobs::field_archive_id], true, $job[dbSyncDataJobs::field_total_time]);
					}
				}
				$finished = true;
			}
			// Datensicherung ist abgeschlossen
			if ($finished) return $this->updateFinish($job_id, $job[dbSyncDataJobs::field_archive_id], $job[dbSyncDataJobs::field_total_time]);
		}	
		// in allen anderen Faellen ist nichts zu tun
		return false;
	} // updateContinue()
	
} // class syncDataInterface

?>