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

// load language file
$lang = (dirname ( __FILE__ )) . "/languages/" . LANGUAGE . ".php";
require_once (! file_exists ( $lang ) ? (dirname ( __FILE__ )) . "/languages/EN.php" : $lang);

// load class only once
if (! class_exists ( 'dbConnectLE' )) {
	
	class dbConnectLE {
		
		public $isConnected = false;
		
		private $error = '';
		private $tableName = '';
		private $field_PrimaryKey = '';
		private $allowedHTMLtags = '';
		private $simulate = false;
		private $decode_special_chars = false; // DEPRECATED, encoding and decoding of special chars is no longer supported!
		private $sqlcode = '';
		private $engine = self::engine_myisam;
		private $charset = self::charset_utf8;
		private $collate = self::collate_utf8_general_ci;
		private $auto_increment = 1;
		protected $fieldDefinition = array ();
		protected $fields = array ();
		protected $searchableFields = array ();
		private $module_name = '';
		private $module_directory = '';
		protected $htmlFields = array ();
		protected $indexFields = array ();
		protected $foreignKeys = array ();
		protected $csvMustFields = array ();
		
		const engine_myisam = 'MyISAM';
		const engine_innodb = 'InnoDB';
		const engine_heap = 'HEAP';
		
		const charset_latin1 = 'latin1';
		const charset_latin2 = 'latin2';
		const charset_utf8 = 'utf8';
		
		const collate_latin1_bin = 'latin1_bin';
		const collate_latin1_danish_ci = 'latin1_danish_ci';
		const collate_latin1_general_ci = 'latin1_general_ci';
		const collate_latin1_general_cs = 'latin1_general_cs';
		const collate_latin1_german1_ci = 'latin1_german1_ci';
		const collate_latin1_german2_ci = 'latin1_german2_ci';
		const collate_latin1_spanish_ci = 'latin1_spanish_ci';
		const collate_latin1_swedish_ci = 'latin1_swedish_ci';
		const collate_utf8_general_ci = 'utf8_general_ci';
		const collate_utf8_unicode_ci = 'utf8_unicode_ci';
		
		protected $foreignKey = array ('field' => '', 'foreign_table' => '', 'foreign_key' => '' );
		
		/**
		 * Constructor for class dbConnectLE
		 */
		public function __construct() {
			$this->isConnected = true;
		} // __construct()
		

		/**
		 * Destuctor for class dbConnectLE
		 */
		public function __destruct() {
			$this->isConnected = false;
		} // __destruct()
		

		/**
		 * Execute MySQL Queries
		 * @param string $query
		 * @param reference object $result
		 * @return boolean
		 */
		private function query($query, &$result = false) {
			global $database;
			if (! $this->isConnected) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, dbc_error_database_not_connected ) );
				return false;
			}
			try {
				$result = $database->query ( $query );
				if ($database->is_error ()) {
					throw new Exception ( $database->get_error () );
				}
				return true;
			} catch ( Exception $except ) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, $except->getLine (), $except->getMessage () ) );
				return false;
			}
		} // query()
		

		/**
		 * Checking the error status of the class
		 * @return boolean
		 */
		public function isError() {
			return ( bool ) ! empty ( $this->error );
		} // isError()
		

		/**
		 * Setting an error to the class
		 * @param string $error
		 */
		public function setError($error = '') {
			$this->error = $error;
		} // setError()
		

		/**
		 * Getting the actual error of the class
		 * @return string error message
		 */
		public function getError() {
			return $this->error;
		} // getError()
		

		/**
		 * Removing the last error
		 * 
		 * Reset the last error and set the state of the class to "no error"
		 */
		public function resetError() {
			$this->error = '';
		} // resetError()
		

		/**
		 * Setting the table name
		 * 
		 * Use only the pure table name without any table prefix
		 * @param string $tableName
		 */
		public function setTableName($tableName) {
			$this->tableName = $tableName;
		} // setTableName()
		

		/**
		 * Return the table name
		 * 
		 * The function returns the complete table name including the 
		 * leading TABLE_PREFIX used by WebsiteBaker
		 * @return string tablename
		 */
		public function getTableName() {
			return TABLE_PREFIX . $this->tableName;
		} // getTableName()
		

		/**
		 * Set the primary key for the table
		 * 
		 * dbConnectLE does not allow to create tables without a primary key. 
		 * The primary key will be set by by the function dbConnectLE::addFieldDefinition.
		 * @param string $primaryKey - field which is used as primary key
		 */
		public function setField_PrimaryKey($field) {
			$this->field_PrimaryKey = $field;
		} // setField_PrimaryKey()
		

		/**
		 * Return the field set as primary key
		 * @return string $field
		 */
		public function getField_PrimaryKey() {
			return $this->field_PrimaryKey;
		} // getField_PrimaryKey()
		

		/**
		 * Set allowed HTML tags
		 * 
		 * By default dbConnectLE strips HTML tags from all fields before writing to 
		 * database. If you want use HTML tags in a desired field you must set the flag
		 * 'allowHTML' using the function dbConnectLE::addFieldDefinition(). Anyway
		 * dbConnectLE allows also in HTML fields only the defined tags!
		 * Use this function in addition to the field definition in the constructor
		 * of your table.
		 * 
		 * @param string $tags allowed html tags
		 * @example setAllowedHTMLtags('<p><span><div><b><i>')
		 */
		public function setAllowedHTMLtags($tags) {
			$this->allowedHTMLtags = $tags;
		} // setAllowedHTMLtags()
		

		/**
		 * Get allowed HTML tags
		 * @return string all allowed HTML tags
		 */
		public function getAllowedHTMLtags() {
			return $this->allowedHTMLtags;
		} // getAllowedHTMLtags()
		

		/**
		 * Set the name of the MODULE dbConnectLE is used for
		 *
		 * dbConnectLE must know the MODULE name and directory if you make FIELDS
		 * searchable and want to use the dbConnectLE::sqlAddSearchFeature()
		 * @param string $moduleName
		 */
		public function setModuleName($moduleName) {
			$this->module_name = $moduleName;
		}
		
		/**
		 * Get the name of the MODULE dbConnectLE is used for
		 *
		 * dbConnectLE must know the MODULE name and directory if you make FIELDS
		 * searchable and want to use the dbConnectLE::sqlAddSearchFeature()
		 * @return string
		 */
		public function getModuleName() {
			return $this->module_name;
		} // getModuleName()
		

		/**
		 * Set the directory of the MODULE dbConnectLE is used for
		 *
		 * dbConnectLE must know the MODULE name and directory if you make FIELDS
		 * searchable and want to use the dbConnectLE::sqlAddSearchFeature()
		 * @param string $moduleDirectory
		 */
		public function setModuleDirectory($moduleDirectory) {
			$this->module_directory = $moduleDirectory;
		} // setModuleDirectory()
		

		/**
		 * Get the directory of the MODULE dbConnectLE is used for
		 *
		 * dbConnectLE must know the MODULE name and directory if you make FIELDS
		 * searchable and want to use the dbConnectLE::sqlAddSearchFeature()
		 * @return string
		 */
		public function getModuleDirectory() {
			return $this->module_directory;
		}
		
		/**
		 * Switch the simulation mode on or off
		 *
		 * Sometimes it is usefull not to execute the SQL commands and only to simulate
		 * the command. Setting dbConnectLE::simulation(true) will switch dbConnectLE to
		 * a sandbox mode and nothing will be written to the database.
		 * @param boolean $switchON
		 */
		public function simulation($switchON) {
			$this->simulate = ( boolean ) $switchON;
		}
		
		/**
		 * Switch the decoding of special chars on or off
		 * 
		 * dbConnectLE does not support the encoding and decoding of special chars, i.e.
		 * "Umlaute". This function is only needed for compatibility with the predecessor 
		 * class "dbConnect". 
		 * @param BOOL $decode
		 * @deprecated 
		 */
		public function setDecodeSpecialChars($decode = true) {
			$this->decode_special_chars = false; //$decode;
		}
		
		/**
		 * SET SQL Code
		 *
		 * This function is used by all dbConnectLE::sql* functions to hold the generated
		 * SQL string within a protected field. Call dbConnect::getSQL() in simulation mode
		 * or after executing an dbConnectLE::sql* function to get the generated and executed
		 * SQL command. 
		 * @param string $sqlcode
		 */
		protected function setSQL($sqlcode) {
			$this->sqlcode = $sqlcode;
		}
		
		/**
		 * GET SQL Code
		 *
		 * Call dbConnect::getSQL() in simulation mode or after executing an 
		 * dbConnectLE::sql* function to get the generated and executed SQL command.
		 * @return string $sqlcode
		 */
		public function getSQL() {
			return $this->sqlcode;
		}
		
		/**
		 * Execute $sqlCode and return $result if possible
		 *
		 * Use dbConnectLE::sqlExec() to execute your own SQL commands. If possible this
		 * function returns in the referenced $result an associative array with the SQL 
		 * result or an empty array.
		 * @param string $sqlCode
		 * @param reference array $result
		 * @return boolean
		 */
		public function sqlExec($sqlCode, &$result) {
			$result = array ();
			if ($this->isError ()) {
				return false;
			}
			$this->setSQL ( $sqlCode );
			if ($this->simulate)
				return true;
			try {
				$sql_result = false;
				if (! $this->query ( $this->getSQL (), $sql_result )) {
					throw new Exception ( $this->getError () );
				}
				// $sql_result may be boolean or a object...
				if (is_object ( $sql_result )) {
					$numRows = @$sql_result->numRows ();
					if ($numRows > 0) {
						for($i = 0; $i < $numRows; $i ++) {
							$result [] = $sql_result->fetchRow ( MYSQL_ASSOC );
						}
					}
				} // is_object()
			} catch ( Exception $except ) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, $except->getLine (), sprintf ( dbc_error_execQuery, $except->getMessage () ) ) );
				return false;
			}
			return true;
		} // sqlExec()
		

		/**
		 * Define a FIELD in the table. 
		 * 
		 * Call this function for each field of your table.
		 * @param string $field Identifer
		 * @param string $type SQL Definition of Field Type
		 * @param boolean $primaryKey set TRUE if this Field should be the primary key
		 * @param boolean $makeSearchable set TRUE if this Field should be visible for the WB Search function
		 * @param boolean $allowHTML set TRUE if the Field is allowed to contain HTML tags
		 */
		public function addFieldDefinition($field, $type, $primaryKey = false, $makeSearchable = false, $allowHTML = false) {
			$this->fieldDefinition [] = array ('field' => $field, 'type' => $type );
			$this->fields [$field] = '';
			if ($primaryKey)
				$this->setField_PrimaryKey ( $field );
			if ($makeSearchable)
				$this->searchableFields [] = $field;
			if ($allowHTML)
				$this->htmlFields [] = $field;
			$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, dbc_error_fieldDefinitionsNotChecked ) );
			return true;
		} // addFieldDefinitin()
		

		/**
		 * Return the defined FIELDS of the table
		 * 
		 * @return array $fields
		 */
		public function getFields() {
			return $this->fields;
		} // getFields()
		

		/**
		 * Checks the FIELD definitions of the table
		 * 
		 * Call this function after complete call of dbConnectLE::addFieldDefinition() and 
		 * before you create the table
		 * @return boolean
		 */
		public function checkFieldDefinitions() {
			// empty table name
			if (strlen ( $this->tableName ) == 0) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, dbc_error_emptyTableName ) );
				return false;
			}
			// no field definitions...
			if (count ( $this->fieldDefinition ) < 1) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, dbc_error_noFieldDefinitions ) );
				return false;
			}
			// no primary key
			if (strlen ( $this->field_PrimaryKey ) == 0) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, dbc_error_noPrimaryKey ) );
				return false;
			}
			// ok - no error switch to  no_error
			$this->resetError ();
			return true;
		} // checkFieldDefinitions()
		

		/**
		 * Set INDEX fields for the table
		 * 
		 * To define additional INDEX fields use this function.
		 * @param array $index_fields
		 * @link http://dev.mysql.com/doc/refman/5.5/en/mysql-indexes.html
		 */
		public function setIndexFields($index_fields = array()) {
			$this->indexFields = $index_fields;
		} // setIndexFields
		

		/**
		 * Get an array of the defined INDEX fields of the table
		 * @return array $indexFields
		 * @link http://dev.mysql.com/doc/refman/5.5/en/mysql-indexes.html
		 */
		public function getIndexFields() {
			return $this->indexFields;
		} // getIndexFields()
		

		/**
		 * Set FOREIGN keys for the table
		 * 
		 * To define additional FOREIGN keys use this function.
		 * @param array $foreign_keys
		 * @example dbConnectLE::setForeignKeys(array('field' => $field, 
		 * 'foreign_table' => $foreign_table, 'foreign_key' => $foreign_key)) 
		 * @link http://dev.mysql.com/doc/refman/5.1/en/ansi-diff-foreign-keys.html
		 */
		public function setForeignKeys($foreign_keys = array()) {
			$this->foreignKeys = $foreign_keys;
		} // setForeignKeys()
		

		/**
		 * Get an array of the defined FOREIGN keys of the table
		 * @return array $foreignKeys
		 * @link http://dev.mysql.com/doc/refman/5.1/en/ansi-diff-foreign-keys.html
		 */
		public function getForeignKeys() {
			return $this->foreignKeys;
		} // getForeignKeys()
		

		/**
		 * Set the database ENGINE
		 * 
		 * Allowed values are 'MyISAM', 'InnoDB' or 'HEAP' - please use the predefined
		 * constants dbConnectLE::engine_myisam, dbConnectLE::engine_innodb or
		 * dbConnectLE::engine_heap.
		 * @param string $engine
		 */
		public function setEngine($engine) {
			$this->engine = $engine;
		} // setEngine()
		
		public function setAutoIncrement($auto_increment) {
			$this->auto_increment = $auto_increment;
		} // setAutoIncrement()
		
		/**
		 * Get the defined database ENGINE
		 * @return string $engine
		 */
		public function getEngine() {
			return $this->engine;
		} // getEngine()
		
		/**
		 * Get the defined AUTO_INCREMENT start value
		 * @return INT $auto_increment
		 */
		public function getAutoIncrement() {
			return $this->auto_increment;
		} // getAutoIncrement()
		
		/**
		 * Set the CHARSET for the table
		 * @param string $charset
		 */
		public function setCharset($charset) {
			$this->charset = $charset;
		} // setCharset()
		

		/**
		 * Get the defined CHARSET of the table
		 * @return string $charset
		 */
		public function getCharset() {
			return $this->charset;
		} // getCharset()
		

		/**
		 * Set the COLLATION for the table
		 * @param string $collate
		 */
		public function setCollate($collate) {
			$this->collate = $collate;
		} // setCollate()
		

		/**
		 * Get the defined COLLATION of the table
		 * @return string $collate
		 */
		public function getCollate() {
			return $this->collate;
		} // getCollate()
		

		/**
		 * Setting CHARSET and COLLATION at once
		 * @param string $charset
		 * @param string $collate
		 */
		public function setDefaultCharset($charset, $collate) {
			$this->setCharset ( $charset );
			$this->setCollate ( $collate );
		} // setDefaultCharset()
		

		/**
		 * Create the defined table
		 *
		 * Create the table only if it NOT EXISTS and use all desired params like 
		 * PRIMARY KEY, INDEX fields, FOREIGN keys, ENGINE, CHARSET and COLLATE.
		 * If you want to overwrite an existing table use dbConnectLE::sqlDeleteTable()
		 * first.
		 * @return boolean
		 */
		public function sqlCreateTable() {
			// if class has already error status return false and exit
			if ($this->isError ()) {
				return false;
			}
			$sqlQuery = sprintf ( 'CREATE TABLE IF NOT EXISTS `%s` ( ', $this->getTableName () );
			for($i = 0; $i < count ( $this->fieldDefinition ); $i ++) {
				$sqlQuery .= sprintf ( "`%s` %s,", $this->fieldDefinition [$i] ['field'], $this->fieldDefinition [$i] ['type'] );
			}
			$sqlQuery .= sprintf ( 'PRIMARY KEY (%s)', $this->getField_PrimaryKey () );
			if (count ( $this->getIndexFields () ) > 0) {
				$index = '';
				foreach ( $this->getIndexFields () as $field ) {
					if ($field != $this->getField_PrimaryKey ()) {
						(empty ( $index )) ? $index = $field : $index .= ',' . $field;
					}
				}
				if (! empty ( $index ))
					$sqlQuery .= sprintf ( ',KEY (%s)', $index );
			}
			
			if (count ( $this->getForeignKeys () ) > 0) {
				$foreign = '';
				foreach ( $this->foreignKeys as $key ) {
					$foreign .= sprintf ( ',FOREIGN KEY (%s) REFERENCES %s (%s)', $key ['field'], $key ['foreign_table'], $key ['foreign_field'] );
				}
				$sqlQuery .= $foreign;
				$this->setEngine ( 'InnoDB' );
			}
			$sqlQuery .= sprintf ( ') ENGINE = %s AUTO_INCREMENT = %s DEFAULT CHARSET = %s COLLATE = %s', $this->getEngine (), $this->auto_increment, $this->getCharset (), $this->getCollate () );
			$this->setSQL ( $sqlQuery );
			if ($this->simulate)
				return true;
			try {
				$sql_result = array ();
				if (! $this->query ( $this->getSQL (), $sql_result )) {
					throw new Exception ( $this->getError () );
				}
			} catch ( Exception $e ) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, $e->getLine (), sprintf ( dbc_error_execQuery, $e->getMessage () ) ) );
				return false;
			}
			return true;
		} // sqlCreateTable()
		

		/**
		 * Delete (DROP) the table if exists
		 * @return boolean
		 */
		public function sqlDeleteTable() {
			$this->setSQL ( "DROP TABLE IF EXISTS " . $this->getTableName () );
			if ($this->simulate)
				return true;
			try {
				$sql_result = array ();
				if (! $this->query ( $this->getSQL (), $sql_result )) {
					throw new Exception ( $this->getError () );
				}
			} catch ( Exception $e ) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, $e->getLine (), sprintf ( dbc_error_execQuery, $e->getMessage () ) ) );
				return false;
			}
			return true;
		} // sqlDeleteTable()
		

		/**
		 * Add the global WebsiteBaker search feature for the desired MODULE
		 *
		 * To use this function you must define the MODULE name and directory with
		 * dbConnectLE::setModuleName() and dbConnectLE::setModuleDirectory() and
		 * you must set the $makeSearchable flag at dbConnectLE::addFieldDefinition()
		 * for the fields which should be searchable.
		 * @return boolean
		 */
		public function sqlAddSearchFeature() {
			global $database;
			/**
			 * check if all needed variables are defined
			 */
			if (empty ( $this->tableName )) {
				$this->setError ( sprintf ( dbc_error_emptyTableName, __METHOD__, __LINE__ ) );
				return false;
			}
			if (empty ( $this->module_directory )) {
				$this->setError ( sprintf ( dbc_error_noModuleDirectory, __METHOD__, __LINE__ ) );
				return false;
			}
			if (empty ( $this->field_PageID )) {
				$this->setError ( sprintf ( dbc_error_noPageIDField, __METHOD__, __LINE__ ) );
				return false;
			}
			// check searchable fields
			if (count ( $this->searchableFields ) < 1) {
				$this->setError ( sprintf ( dbc_error_noSearchableFields, __METHOD__, __LINE__ ) );
				return false;
			} else {
				for($i = 0; $i < count ( $this->searchableFields ); $i ++) {
					if (($this->searchableFields [$i] == $this->field_PageID) || ($this->searchableFields [$i] == $this->field_PrimaryKey)) {
						$this->setError ( sprintf ( dbc_error_invalidSearchableField, __METHOD__, __LINE__, $this->searchableFields [$i] ) );
						return false;
					}
				}
			}
			// insert info into the search table
			$search_info = array ('page_id' => 'page_id', 'title' => 'page_title', 'link' => 'link', 'description' => 'description', 'modified_when' => 'modified_when', 'modified_by' => 'modified_by' );
			$search_info = serialize ( $search_info );
			$moduleDirectory = $this->module_directory;
			$tableName = $this->getTableName ();
			$fieldPageID = $this->field_PageID;
			$this->setSQL ( "INSERT INTO " . TABLE_PREFIX . "search (name,value,extra)	VALUES ('module', '$moduleDirectory', '$search_info')" );
			if ($this->simulate) {
				$simulateSQL = $this->getSQL ();
			} else {
				try {
					@$database->query ( $this->getSQL () );
					if ($database->is_error ()) {
						throw new Exception ( $database->get_error () );
					}
				} catch ( Exception $except ) {
					$this->setError ( sprintf ( dbc_error_execQuery, __METHOD__, $except->getLine (), $except->getMessage () ) );
					return false;
				}
			}
			// Search query start code
			$query_start_code = "SELECT [TP]pages.page_id, [TP]pages.page_title,	[TP]pages.link, [TP]pages.description,
  	                       [TP]pages.modified_when, [TP]pages.modified_by	FROM [TP]$tableName, [TP]pages WHERE ";
			$this->setSQL ( "INSERT INTO " . TABLE_PREFIX . "search (name, value, extra) VALUES ('query_start', '$query_start_code', '$moduleDirectory')" );
			if ($this->simulate) {
				$simulateSQL .= " " . $this->getSQL ();
			} else {
				try {
					@$database->query ( $this->getSQL () );
					if ($database->is_error ()) {
						throw new Exception ( $database->get_error () );
					}
				} catch ( Exception $except ) {
					$this->setError ( sprintf ( dbc_error_execQuery, __METHOD__, $except->getLine (), $except->getMessage () ) );
					return false;
				}
			}
			// Search query body code
			$query_body_code = "";
			for($i = 0; $i < count ( $this->searchableFields ); $i ++) {
				if ($i > 0) {
					$query_body_code .= " OR ";
				}
				$field = $this->searchableFields [$i];
				$query_body_code .= "[TP]pages.page_id = [TP]$tableName.$fieldPageID AND [TP]$tableName.$field LIKE \'%[STRING]%\' AND [TP]pages.searching = \'1\'";
			}
			$this->setSQL ( "INSERT INTO " . TABLE_PREFIX . "search (name, value, extra) VALUES ('query_body', '$query_body_code', '$moduleDirectory')" );
			if ($this->simulate) {
				$simulateSQL .= " " . $this->getSQL ();
			} else {
				try {
					@$database->query ( $this->getSQL () );
					if ($database->is_error ()) {
						throw new Exception ( $database->get_error () );
					}
				} catch ( Exception $except ) {
					$this->setError ( sprintf ( dbc_error_execQuery, __METHOD__, $except->getLine (), $except->getMessage () ) );
					return false;
				}
			}
			// Search query end code
			$query_end_code = "";
			$this->setSQL ( "INSERT INTO " . TABLE_PREFIX . "search (name, value, extra) VALUES ('query_end', '$query_end_code', '$moduleDirectory')" );
			if ($this->simulate) {
				$simulateSQL .= " " . $this->getSQL ();
				$this->setSQL ( $simulateSQL );
				return true;
			} else {
				try {
					@$database->query ( $this->getSQL () );
					if ($database->is_error ()) {
						throw new Exception ( $database->get_error () );
					}
				} catch ( Exception $except ) {
					$this->setError ( sprintf ( dbc_error_execQuery, __METHOD__, $except->getLine (), $except->getMessage () ) );
					return false;
				}
			}
			// Insert a blank row in module database for search function to work...
			return true;
		} // sqlAddSearchFeature()
		

		/**
		 * Remove the global WebsiteBaker search feature for the desired MODULE
		 * @return boolean
		 */
		public function sqlRemoveSearchFeature() {
			global $database;
			if (empty ( $this->module_directory )) {
				$this->setError ( sprintf ( dbc_error_noModuleDirectory, __METHOD__, __LINE__ ) );
				return false;
			}
			$moduleDirectory = $this->module_directory;
			$this->setSQL ( "DELETE FROM " . TABLE_PREFIX . "search WHERE name='module' AND value='$moduleDirectory'" );
			if ($this->simulate) {
				$simulateSQL = $this->getSQL ();
			} else {
				try {
					@$database->query ( $this->getSQL () );
					if ($database->is_error ()) {
						throw new Exception ( $database->get_error () );
					}
				} catch ( Exception $except ) {
					$this->setError ( sprintf ( dbc_error_execQuery, __METHOD__, $except->getLine (), $except->getMessage () ) );
					return false;
				}
			}
			$this->setSQL ( "DELETE FROM " . TABLE_PREFIX . "search WHERE extra='$moduleDirectory'" );
			if ($this->simulate) {
				$simulateSQL .= " " . $this->getSQL ();
				$this->setSQL ( $simulateSQL );
				return true;
			} else {
				try {
					@$database->query ( $this->getSQL () );
					if ($database->is_error ()) {
						throw new Exception ( $database->get_error () );
					}
				} catch ( Exception $except ) {
					$this->setError ( sprintf ( dbc_error_execQuery, __METHOD__, $except->getLine (), $except->getMessage () ) );
					return false;
				}
			}
			return true;
		} // sqlRemoveSearchFeature()
		

		/**
		 * Encode special chars with HTML entities
		 * @param reference string &$string
		 * @return string
		 * @deprecated - this function was used with dbConnect and is no longer supported!
		 */
		private function encodeSpecialChars(&$string) {
			$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, dbc_error_feature_not_supported ) );
			return $string;
		} // encodeSpecialChars()
		

		/**
		 * Decode special chars with html entities
		 * @param reference string &$string
		 * @return string
		 * @deprecated - this function was used with dbConnect and is no longer supported!
		 */
		private function decodeSpecialChars(&$string) {
			$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, dbc_error_feature_not_supported ) );
			return $string;
		} // decodeSpecialChars()
		

		/**
		 * Prepare incoming data by decoding special chars, strip tags
		 * and adding slashes...
		 * @param $string incoming data
		 * @param boolean strip HTML tags
		 * @return string prepared data
		 */
		protected function prepareIncomingData($string, $stripTags = true) {
			if ($stripTags) {
				return addslashes ( strip_tags ( $string ) );
			} elseif (empty ( $this->allowedHTMLtags )) {
				return addslashes ( $string );
			} else {
				return addslashes ( strip_tags ( $string, $this->allowedHTMLtags ) );
			}
		} // prepareIncomingData()
		

		/**
		 * Check the delivered data before transfering to the database
		 *
		 * @param reference array $data
		 * @param boolean $allowPrimaryKey
		 * @return boolean
		 */
		protected function checkIncomingData(&$data, $allowPrimaryKey = false) {
			// walk through the $data Array an check values
			reset ( $data );
			$checked = array ();
			while ( false !== (list ( $key, $val ) = each ( $data )) ) {
				if (key_exists ( $key, $this->fields )) {
					if ($key == $this->field_PrimaryKey) {
						if ($allowPrimaryKey) {
							$checked [$key] = $this->prepareIncomingData ( $val );
						}
					} else {
						if (in_array ( $key, $this->htmlFields )) {
							$checked [$key] = $this->prepareIncomingData ( $val, false );
						} else {
							$checked [$key] = $this->prepareIncomingData ( $val );
						}
					}
				}
			}
			// replace $record with $data
			$data = $checked;
			// check for errors
			if (count ( $data ) < 1) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, dbc_error_recordEmpty ) );
				return false;
			}
			return true;
		} // checkIncomingData()
		

		/**
		 * Insert the $data as new record to the table
		 * 
		 * Data must be transmitted as array in the form:
		 * array("field_1" => "value_1", "field_2" => "value_2" [...])
		 * The field which is configured as PRIMARY KEY will be ignored.
		 * $id return the ID of the inserted record.
		 * @param array $data
		 * @param reference integer $id - ID of the inserted record
		 * @return boolean
		 */
		public function sqlInsertRecord($data, &$id = -1) {
			// check $data first
			if (! $this->checkIncomingData ( $data ))
				return false;
			$id = - 1;
			$thisQuery = "INSERT INTO " . $this->getTableName () . " SET ";
			reset ( $data );
			$start = true;
			while ( false !== (list ( $key, $val ) = each ( $data )) ) {
				($start) ? $start = false : $thisQuery .= ",";
				$thisQuery .= "$key='$val'";
			}
			$this->setSQL ( $thisQuery );
			if ($this->simulate)
				return true;
			try {
				$sql_result = array ();
				if (! $this->query ( $this->getSQL (), $sql_result )) {
					throw new Exception ( $this->getError () );
				}
			} catch ( Exception $except ) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, $except->getLine (), sprintf ( dbc_error_execQuery, $except->getMessage () ) ) );
				return false;
			}
			$id = mysql_insert_id ();
			return true;
		} // sqlInsertRecord() 
		

		/**
		 * Update the record $where with the data $data.
		 * 
		 * $data and $where must be transmitted as array in the form:
		 * array("field_1" => "value_1", "field_2" => "value_2" [...]).
		 * Primary Key will be ignored in $data array.
		 * @param array $data
		 * @param array $where
		 * @return boolean
		 */
		public function sqlUpdateRecord($data, $where) {
			// check $data first
			if (! $this->checkIncomingData ( $data ))
				return false;
			if (! $this->checkIncomingData ( $where, true ))
				return false;
			$thisQuery = "UPDATE " . $this->getTableName () . " SET ";
			reset ( $data );
			$start = true;
			while ( false !== (list ( $key, $val ) = each ( $data )) ) {
				($start) ? $start = false : $thisQuery .= ",";
				$val = trim ( $val );
				$thisQuery .= "$key='$val'";
			}
			reset ( $where );
			$start = true;
			$thisQuery .= " WHERE ";
			while ( false !== (list ( $key, $val ) = each ( $where )) ) {
				($start) ? $start = false : $thisQuery .= " AND ";
				$thisQuery .= "$key='$val'";
			}
			$this->setSQL ( $thisQuery );
			if ($this->simulate)
				return true;
			try {
				$sql_result = array ();
				if (! $this->query ( $this->getSQL (), $sql_result )) {
					throw new Exception ( $this->getError () );
				}
			} catch ( Exception $except ) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, $except->getLine (), sprintf ( dbc_error_execQuery, $except->getMessage () ) ) );
				return false;
			}
			return true;
		} // sqlUpdateRecord()
		

		/**
		 * SELECT record(s) matching to $where. Return records in $result
		 * 
		 * $where must be transmitted as array in the form:
		 * array("field_1" => "value_1", "field_2" => "value_2" [...]).
		 * If $where is empty function return all records in table
		 *
		 * @param array $where
		 * @param reference array $result
		 * @return boolean
		 */
		public function sqlSelectRecord($where = array(), &$result = array()) {
			$result = array ();
			if (empty ( $where )) {
				// select All
				$thisQuery = "SELECT * FROM " . $this->getTableName ();
			} else {
				// check $where first
				if (! $this->checkIncomingData ( $where, true ))
					return false;
				$thisQuery = "SELECT * FROM " . $this->getTableName () . " WHERE ";
				reset ( $where );
				$start = true;
				while ( false !== (list ( $key, $val ) = each ( $where )) ) {
					($start) ? $start = false : $thisQuery .= " AND ";
					$thisQuery .= "$key='$val'";
				}
			}
			$this->setSQL ( $thisQuery );
			if ($this->simulate)
				return true;
			try {
				$sql_result = array ();
				if (! $this->query ( $this->getSQL (), $sql_result )) {
					throw new Exception ( $this->getError () );
				}
			} catch ( Exception $except ) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, $except->getLine (), sprintf ( dbc_error_execQuery, $except->getMessage () ) ) );
				return false;
			}
			$numRows = $sql_result->numRows ();
			if ($numRows > 0) {
				for($i = 0; $i < $numRows; $i ++) {
					$check = $sql_result->fetchRow ( MYSQL_ASSOC );
					while ( false !== (list ( $key, $val ) = each ( $check )) ) {
						if (key_exists ( $key, $this->fields )) {
							$result [$i] [$key] = stripslashes ( $val );
						}
					}
				}
			}
			return true;
		} // sqlSelectRecord()
		

		/**
		 * SELECT record(s) matching to $where ORDER BY $orderBy.
		 * 
		 * Return records in $result $where must be transmitted as array in the form:
		 * array("field_1" => "value_1", "field_2" => "value_2" [...]).
		 * If $where is empty function return all records in table
		 * $orderBy must be transmitted as simple array, containing the fieldnames.
		 * @param array $where
		 * @param reference array $result
		 * @param array $orderBy fieldnames
		 * @param boolean $ascending 
		 * @return boolean
		 */
		public function sqlSelectRecordOrderBy($where, &$result, $orderBy, $ascending = true) {
			$result = array ();
			if (empty ( $where )) {
				// select All
				$thisQuery = "SELECT * FROM " . $this->getTableName ();
				if (! empty ( $orderBy )) {
					// ORDER BY
					$start = true;
					$thisQuery .= " ORDER BY ";
					for($i = 0; $i < sizeof ( $orderBy ); $i ++) {
						($start == true) ? $start = false : $thisQuery .= ", ";
						$thisQuery .= $orderBy [$i];
					}
					($ascending == true) ? $thisQuery .= " ASC" : $thisQuery .= " DESC";
				}
			} else {
				// check $where first
				if (! $this->checkIncomingData ( $where, true ))
					return false;
				$thisQuery = "SELECT * FROM " . $this->getTableName () . " WHERE ";
				reset ( $where );
				$start = true;
				while ( false !== (list ( $key, $val ) = each ( $where )) ) {
					($start == true) ? $start = false : $thisQuery .= " AND ";
					$thisQuery .= "$key='$val'";
				}
				if (! empty ( $orderBy )) {
					// ORDER BY
					$start = true;
					$thisQuery .= " ORDER BY ";
					for($i = 0; $i < sizeof ( $orderBy ); $i ++) {
						($start == true) ? $start = false : $thisQuery .= ", ";
						$thisQuery .= $orderBy [$i];
					}
					($ascending == true) ? $thisQuery .= " ASC" : $thisQuery .= " DESC";
				}
			}
			$this->setSQL ( $thisQuery );
			if ($this->simulate)
				return true;
			try {
				$sql_result = array ();
				if (! $this->query ( $this->getSQL (), $sql_result )) {
					throw new Exception ( $this->getError () );
				}
			} catch ( Exception $except ) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, $except->getLine (), sprintf ( dbc_error_execQuery, $except->getMessage () ) ) );
				return false;
			}
			$numRows = $sql_result->numRows ();
			if ($numRows > 0) {
				for($i = 0; $i < $numRows; $i ++) {
					$check = $sql_result->fetchRow ( MYSQL_ASSOC );
					while ( false !== (list ( $key, $val ) = each ( $check )) ) {
						if (key_exists ( $key, $this->fields )) {
							$result [$i] [$key] = stripslashes ( $val );
						}
					}
				}
			}
			return true;
		} // sqlSelectRecordOrderBy()
		

		/**
		 * DELETE record(s) matching to $where.
		 * 
		 * $where must be transmitted as array in the form:
		 * array("field_1" => "value_1", "field_2" => "value_2" [...]).
		 * ATTENTION: if $where is EMPTY function DELETE ALL RECORDS!!!
		 * @param array $where
		 * @return boolean
		 */
		public function sqlDeleteRecord($where) {
			if (empty ( $where )) {
				// DELETE ALL RECORDS !!!
				$thisQuery = "DELETE FROM " . $this->getTableName ();
			} else {
				// check $where first
				if (! $this->checkIncomingData ( $where, true ))
					return false;
				$thisQuery = "DELETE FROM " . $this->getTableName () . " WHERE ";
				reset ( $where );
				$start = true;
				while ( false !== (list ( $key, $val ) = each ( $where )) ) {
					($start) ? $start = false : $thisQuery .= " AND ";
					$thisQuery .= "$key='$val'";
				}
			}
			$this->setSQL ( $thisQuery );
			if ($this->simulate)
				return true;
			try {
				$sql_result = array ();
				if (! $this->query ( $this->getSQL (), $sql_result )) {
					throw new Exception ( $this->getError () );
				}
			} catch ( Exception $except ) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, $except->getLine (), sprintf ( dbc_error_execQuery, $except->getMessage () ) ) );
				return false;
			}
			return true;
		} // sqlDeleteRecord()
		

		/**
		 * DESCRIBE Table, returns $result ARRAY with fields:
		 * [Field], [Type], [Null], [Key], [Default] and [Extra]
		 * for each entry
		 * @param refrence array $result
		 * @return boolean
		 */
		public function sqlDescribeTable(&$result) {
			$this->setSQL ( "DESCRIBE " . $this->getTableName () );
			if ($this->simulate)
				return true;
			try {
				$sql_result = array ();
				if (! $this->query ( $this->getSQL (), $sql_result )) {
					throw new Exception ( $this->getError () );
				}
				while ( false !== ($data = $sql_result->fetchRow ( MYSQL_ASSOC )) ) {
					$result [] = $data;
				}
			} catch ( Exception $except ) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, $except->getLine (), sprintf ( dbc_error_execQuery, $except->getMessage () ) ) );
				return false;
			}
			return true;
		} // sqlDescribeTable()
		

		/**
		 * Check if $field exists in table 
		 * @param string $field
		 * @return boolean
		 */
		public function sqlFieldExists($field) {
			$describe = array ();
			$this->sqlDescribeTable ( $describe );
			foreach ( $describe as $row ) {
				if ($row ['Field'] == $field) {
					// field already exist - exit
					return true;
				}
			}
			return false;
		} // sqlFieldExists()
		

		/**
		 * ALTER TABLE and ADD $field with $type
		 * 
		 * If $after_field is empty, the new field will be placed
		 * as first field in the table otherwise behind the specified
		 * field.
		 * Return TRUE on success or if $field already exists
		 * @param string $field
		 * @param string $type
		 * @param string $after_field
		 * @return boolean
		 */
		public function sqlAlterTableAddField($field, $type, $after_field = '') {
			$describe = array ();
			$this->sqlDescribeTable ( $describe );
			foreach ( $describe as $row ) {
				if ($row ['Field'] == $field) {
					// field already exist - exit
					return true;
				}
			}
			empty ( $after_field ) ? $position = ' FIRST' : $position = ' AFTER ' . $after_field;
			$this->setSQL ( "ALTER TABLE " . $this->getTableName () . " ADD " . $field . " " . $type . $position );
			if ($this->simulate)
				return true;
			try {
				if (! $this->query ( $this->getSQL () )) {
					throw new Exception ( $this->getError () );
				}
			} catch ( Exception $except ) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, $except->getLine (), sprintf ( dbc_error_execQuery, $except->getMessage () ) ) );
				return false;
			}
			return true;
		} // sqlAddField()
		

		/**
		 * ALTER TABLE and DELETE $field
		 * 
		 * Return TRUE on success or if field does NOT EXISTS
		 * @param string $field
		 * @return boolean
		 */
		public function sqlAlterTableDropField($field) {
			$describe = array ();
			$this->sqlDescribeTable ( $describe );
			foreach ( $describe as $row ) {
				if ($row ['Field'] == $field) {
					// Field exists and should be deleted
					$this->setSQL ( "ALTER TABLE " . $this->getTableName () . " DROP " . $field );
					if ($this->simulate)
						return true;
					try {
						if (! $this->query ( $this->getSQL () )) {
							throw new Exception ( $this->getError () );
						} else {
							// success - return true
							return true;
						}
					} catch ( Exception $except ) {
						$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, $except->getLine (), sprintf ( dbc_error_execQuery, $except->getMessage () ) ) );
						return false;
					}
				}
			}
			// field not found - return true
			return true;
		} // sqlAlterTableDeleteField()
		

		/**
		 * ALTER TABLE and CHANGE $old_field
		 * 
		 * Rename to $new_field and set $type 
		 * Return TRUE on success or if field does not exists
		 * @param string $old_field
		 * @param string $new_field
		 * @param string $type
		 * @return boolean
		 */
		public function sqlAlterTableChangeField($old_field, $new_field, $type) {
			$describe = array ();
			$this->sqlDescribeTable ( $describe );
			foreach ( $describe as $row ) {
				if ($row ['Field'] == $old_field) {
					// field exists and should be modified
					$this->setSQL ( "ALTER TABLE " . $this->getTableName () . " CHANGE " . $old_field . " " . $new_field . " " . $type );
					if ($this->simulate)
						return true;
					try {
						if (! $this->query ( $this->getSQL () )) {
							throw new Exception ( $this->getError () );
						} else {
							// success - return true
							return true;
						}
					} catch ( Exception $except ) {
						$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, $except->getLine (), sprintf ( dbc_error_execQuery, $except->getMessage () ) ) );
						return false;
					}
				}
			}
			// field not found - return true
			return true;
		} // sqlAlterTableChangeField()
		

		/**
		 * Check if TABLE exists
		 * @return boolean
		 */
		public function sqlTableExists() {
			$this->setSQL ( sprintf ( "SHOW TABLE STATUS LIKE '%s'", $this->getTableName () ) );
			if ($this->simulate)
				return true;
			try {
				$sql_result = array ();
				if (! $this->query ( $this->getSQL (), $sql_result )) {
					throw new Exception ( $this->getError () );
				}
				($sql_result->numRows () < 1) ? $result = false : $result = true;
				return $result;
			} catch ( Exception $except ) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, $except->getLine (), sprintf ( dbc_error_execQuery, $except->getMessage () ) ) );
				return false;
			}
		} // sqlTableExists()
		
		/**
		 * 
		 * Exec the SQL command FOUND_ROWS() and return the result
		 * @return INT $rows or FALSE on error
		 */
		public function sqlFoundRows() {
			$this->setSQL ( "SELECT FOUND_ROWS()" );
			if ($this->simulate)
				return true;
			try {
				$sql_result = array ();
				if (! $this->query ( $this->getSQL (), $sql_result )) {
					throw new Exception ( $this->getError () );
				}
				$data = $sql_result->fetchRow();
				return (count($data) > 0) ? $data[0] : false;
			} catch ( Exception $except ) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, $except->getLine (), sprintf ( dbc_error_execQuery, $except->getMessage () ) ) );
				return false;
			}
		} // sqlFoundRows()
		
		/**
		 * Import CSV File into the table.
		 * @param $csvFile_path string - path to the CSV file
		 * @param &$importCSV array reference - returns the imported CSV
		 * @param $header boolean true - assume, that 1. line of CSV contain Fielddescriptions
		 * @param $duplicates boolean false - dont import CSV if the record already exists
		 * @param $length integer - default 1000
		 * @param $delimiter string - default ","
		 * @param $enclosure string - default "\""
		 * @return boolean
		 */
		public function csvImport(&$importCSV, $csvFile_path, $header = true, $duplicates = false, $length = 1000, $delimiter = ",", $enclosure = "\"") {
			$start = true;
			$key = array ();
			$importCSV = array ();
			$handle = @fopen ( $csvFile_path, "r" );
			if ($handle === false) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, sprintf ( dbc_error_csv_file_no_handle, basename ( $csvFile_path ) ) ) );
				return false;
			}
			while ( false !== ($record = fgetcsv ( $handle, $length, $delimiter, $enclosure )) ) {
				$num = count ( $record );
				$rec = array ();
				for($i = 0; $i < $num; $i ++) {
					if ($start) {
						if ($header) {
							if (array_key_exists ( $record [$i], $this->fields )) {
								$key [$i] = $record [$i];
							}
						} else {
							// kein Header, Datenreihe!
							$key [$i] = $this->fields [$i];
							$rec [$key [$i]] = $record [$i];
						}
					} else {
						if (sizeof ( $key ) < 1) {
							// Es wurden keine Schluesselfelder erzeugt!
							$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, sprintf ( dbc_error_csv_no_keys, basename ( $csvFile_path ) ) ) );
							return false;
						}
						$rec [$key [$i]] = $record [$i];
					}
				}
				if ($start) {
					$start = false;
					if (! $header) {
						$importCSV [] = $rec;
					}
				} else {
					$importCSV [] = $rec;
				}
			}
			fclose ( $handle );
			foreach ( $importCSV as $record ) {
				if ($duplicates == false) {
					$data = $record;
					if (key_exists ( $this->field_PrimaryKey, $data )) {
						unset ( $data [$this->field_PrimaryKey] );
					}
					$result = array ();
					if (! $this->sqlSelectRecord ( $data, $result )) {
						return false;
					}
					if (sizeof ( $result ) < 1) {
						if (! $this->sqlInsertRecord ( $record )) {
							return false;
						}
					}
				} else {
					if (! $this->sqlInsertRecord ( $record )) {
						return false;
					}
				}
			}
			return true;
		} // csvImport()
		

		/**
		 * Exports the records matching to $where to the CSV File $csvFile_path
		 * @param $where array
		 * @param &$exportCSV array reference - matching records exported to CSV
		 * @param $csvFile_path string - path to the CSV file
		 * @param $header boolean - default TRUE, write 1. line with field descriptions
		 * @param $delimiter string - default ","
		 * @param $enclosure string - default "\""
		 * @return boolean
		 */
		public function csvExport($where, &$exportCSV, $csvFile_path, $header = true, $delimiter = ",", $enclosure = "\"") {
			$exportCSV = array ();
			if (! $this->sqlSelectRecord ( $where, $exportCSV )) {
				return false;
			}
			$handle = @fopen ( $csvFile_path, "w" );
			if ($handle === false) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, sprintf ( dbc_error_csv_file_no_handle, basename ( $csvFile_path ) ) ) );
				return false;
			}
			$start = true;
			foreach ( $exportCSV as $record ) {
				if ($start && $header) {
					$head = array ();
					foreach ( $this->fields as $key => $value ) {
						$head [] = $key;
					}
					$bytes = @fputcsv ( $handle, $head, $delimiter, $enclosure );
					if ($bytes === false) {
						$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, sprintf ( dbc_error_csv_file_put, basename ( $csvFile_path ) ) ) );
						return false;
					}
					$start = false;
				}
				$bytes = @fputcsv ( $handle, $record, $delimiter, $enclosure );
				if ($bytes === false) {
					$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, sprintf ( dbc_error_csv_file_put, basename ( $csvFile_path ) ) ) );
					return false;
				}
			}
			fclose ( $handle );
			return true;
		} // csvExport()
		

		/**
		 * Set the "must have" fields in the CSV array
		 * @param array $mustFields
		 * @return boolean
		 */
		public function csvSetMustFields($mustFields = array()) {
			if (empty ( $mustFields )) {
				// Set Defaults
				$must = $this->fields;
				unset ( $must [$this->field_PrimaryKey] );
				$this->csvMustFields = $must;
				return true;
			} else {
				// use $mustFields
				$must = array ();
				foreach ( $mustFields as $field ) {
					if ((array_key_exists ( $field, $this->fields )) && ($field != $this->field_PrimaryKey)) {
						$must [$field] = '';
					}
				}
				if (sizeof ( $must ) > 0) {
					$this->csvMustFields = $must;
					return true;
				} else {
					// Set Defaults
					$must = $this->fields;
					unset ( $must [$this->field_PrimaryKey] );
					$this->csvMustFields = $must;
					return false;
				}
			}
		} // csvSetMustFields()
		

		/**
		 * Return an array of the defined "must have" fields in the CSV array
		 * @return array
		 */
		public function csvGetMustFields() {
			return $this->csvMustFields;
		} // csvGetMustFields()
		

		/**
		 * Convert MySQL DATETIME to german DATETIME string
		 * @param STR $datetime
		 * @deprecated - this function was used in dbConnect and is no longer supported!
		 */
		public function mySQLdate2datum($datetime) {
			if (strlen ( $datetime ) == 10) {
				$result = substr ( $datetime, 8, 2 );
				$result .= ".";
				$result .= substr ( $datetime, 5, 2 );
				$result .= ".";
				$result .= substr ( $datetime, 0, 4 );
				return $result;
			} elseif (strlen ( $datetime ) == 19) {
				$result = substr ( $datetime, 8, 2 );
				$result .= ".";
				$result .= substr ( $datetime, 5, 2 );
				$result .= ".";
				$result .= substr ( $datetime, 0, 4 );
				$result .= substr ( $datetime, 10 );
				return $result;
			} else {
				return false;
			}
		} // mySQLdate2datum()
	

	} // class database


} // class_exists() 


?>