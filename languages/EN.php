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

define('sync_btn_abort',												'Cancel');
define('sync_btn_ok',														'Apply');
define('sync_btn_continue',											'Continue ...');
define('sync_btn_start',												'Start ...');

define('sync_cfg_currency',											'%s €');
define('sync_cfg_date_separator',								'.');
define('sync_cfg_date_str',											'd.m.Y');
define('sync_cfg_datetime_str',									'd.m.Y H:i');
define('sync_cfg_day_names',										"Sunday, Monday, Tuesday, Wednesday, Thursday, Friday, Saturday");
define('sync_cfg_decimal_separator',          	',');
define('sync_cfg_month_names',									"January, February, March, April, May, June, July, August, September, October, November, December");
define('sync_cfg_thousand_separator',						'.');
define('sync_cfg_time_long_str',								'H:i:s');
define('sync_cfg_time_str',											'H:i');
define('sync_cfg_time_zone',										'Europe/Berlin');
define('sync_cfg_title',												'Mr.,Mrs.');

define('sync_desc_cfg_auto_exec_msec',					'The waiting time in milliseconds until syncData an interrupted process automatically continues. If the value is <b>0</b>, the automatic continuation switched off. The default value is <b>5000</b> milliseconds.');
define('sync_desc_cfg_filemtime_diff_allowed',	'The tolerance for <b>filemtime()</b> comparison in seconds. The default value is 1 second.');
define('sync_desc_cfg_limit_execution_time',		'The limit of script execution in seconds. At reaching the value script execution will be stopped to avoid the maximum execution time.');
define('sync_desc_cfg_max_execution_time',			'Maximum execution time of scripts in seconds. The default value is 30 seconds.');
define('sync_desc_cfg_memory_limit',						'Maximum memory (RAM) for syncData, that can be used for the execution of scripts. The values are stated in <b>bytes</b>, as integer value or <a href="http://it.php.net/manual/de/faq.using.php#faq.using.shorthandbytes" target="_blank">abbreviated byte value</a>, for example "256M".');
define('sync_desc_cfg_ignore_directories',			'Directories which are to be absolutly ignored by syncdata.');
define('sync_desc_cfg_ignore_file_extensions',	'Files with specified extensions are ignored by syncData principle. Separate entries with a comma.');
define('sync_desc_cfg_ignore_tables',						'MySQL tables that are to be absolutly ignored by syncData. Make sure that you use only tables <b>without TABLE_PREFIX</b> (lep_, wb_ etc.)!');
define('sync_desc_cfg_server_active',						'If you share this installation of syncData as a server, you are able to sync other syncData clients to this server.<br />0 = Server OFF, 1 = Server ON');
define('sync_desc_cfg_server_archive_id',				'Choose the <b>ID</b> from the backup archive which should be used for synchronization.');
define('sync_desc_cfg_server_url', 							'If you use this syncData installation as a <b>client</b>, enter the full URL of the syncData <b>server</b>.');

define('sync_error_allow_url_fopen',						'<p>syncData requires the <b>allow_url_fopen = 1</b> setting in <b>php.ini</b> for synchronization.</p>');
define('sync_error_archive_id_invalid',					'<p>To the archive with the ID <b>%s</b> no record was found!</p>');
define('sync_error_archive_missing_ini',				'<p>The archive <b>%s</b> is not a valid syncData archive - missing file <b>sync_data.ini</b>!</p>');
define('sync_error_backup_archive_invalid',			'<p>There was no valid backup specified archive!</p>');
define('sync_error_cfg_id',											'<p>The configuration record with the <b>ID %05d</b> couldn´t be read!</p>');
define('sync_error_cfg_name',										'<p>To the identifier <b>%s</b> no configuration record has been found!</p>');
define('sync_error_copy_file',									'<p>The file <b>%s</b> couldn´t be copied to %s!</p>');
define('snyc_error_dir_not_readable',						'<p>The directory <b>%s</b> is not readable!</p>');
define('sync_error_file_copy',									'<p>The file <b>%s</b> couldn´t be copied to <b>%s</b>.</p>');
define('sync_error_file_delete',								'<p>The file <b>%s</b> couldn´t be deleted.</p>');
define('sync_error_file_get_contents',					'<p>The (<i>remote</i>)file <b>%s</b> couldn´t be read.</p>');
define('sync_error_file_handle',								'<p>There couldn´t be created a file handle for <b>%s</b>!</p>');
define('sync_error_file_list_invalid',					'<p>The file list is invalid..</p>');
define('sync_error_file_list_no_files',					'<p>The file list contains no files for a restore!</p>');
define('sync_error_file_list_no_mysql_files',		'<p>The file list doesn´t contain MySQL files!</p>');
define('sync_error_file_not_exists',						'<p>The file <b>%s</b> doesn´t exist!</p>');
define('sync_error_file_open',									'<p>The file <b>%s</b> couldn´t be opened!</p>');
define('sync_error_file_put_contents',					'<p>The file <b>%s</b> couldn´t be written!</p>');
define('sync_error_file_read',									'<p>The file <b>%s</b> couldn´t be read!</p>');
define('sync_error_file_rename',								'<p>The file <b>%s</b> couldn´t be renamed!</p>');
define('sync_error_file_write',									'<p>Error writing file <b>%s</b>.</p>');
define('sync_error_job_id_invalid',							'<p>Can not find a job with the synData ID <b>%s</b>!</p>');
define('sync_error_mkdir',											'<p>The directory <b>%s</b> couldn´t be created!</p>');
define('sync_error_param_missing_server',				'<p>The Droplet <b>sync_client</b> is missing parameter <b>server</b>. This is necessary in order to adress remote access!</p>');
define('sync_error_preset_not_exists',					'<p>The preset directory <b>%s</b> does not exist, the necessary templates can not be loaded!</p>');
define('sync_error_rmdir',											'<p>The directory <b>%s</b> couldn´t be deleted!</p>');
define('sync_error_status_unknown',							'<p>Unknown status. Please contact the support.</p>');
define('sync_error_sync_action_forbidden',			'<p>Invalid call! Please use only the prescribed parameters for syncData server!</p>');
define('sync_error_sync_archive_filesize',			'<p>The size of the archive file %s could not be determined.</p>');
define('sync_error_sync_archive_file_get_md5',	'<p>Es konnte keine MD5 Prüfsumme für das Archiv %s ermittelt werden!</p><p>Bitte <a href="%s">wiederholen Sie die Aktualisierung</a>!</p>');
define('sync_error_sync_archive_file_missing',	'<p>The archive %s was not found!</p>');
define('sync_error_sync_archive_id_invalid',		'<p>No valid job was found for the syncData archive with the ID <b>%s</b>!</p>');
define('sync_error_sync_archive_id_missing',		'<p>The syncData server is active. However, there was no archive ID set for synchronization.</p>');
define('sync_error_sync_data_corrupt',					'<p>The syncData client can access server information for archive ID <b>%s</b> but is not able to correctly assign.</p>');
define('sync_error_sync_data_ini_missing',			'<p>The archive description <b>sync_data.ini</b> was not found!</p>');
define('sync_error_sync_download_archive_file',	'<p>Fehler beim Download des Archiv <b>%s</b> vom syncData Server!</p><p>Bitte <a href="%s">wiederholen Sie die Aktualisierung</a>!</p>');
define('sync_error_sync_md5_checksum_differ',		'<p>Die für das Archiv <b>%s</b> ermittelte MD5 Prüfsumme weicht von dem Vorgabewert ab.</p><p>Das Archiv ist ungültig und wird verworfen.</p><p>Bitte <a href="%s">wiederholen Sie die Aktualisierung</a>!</p>');
define('sync_error_sync_missing_initial_restore','<p>The basis of synchronization for the archive with the ID <b>%s</b> has been carried out yet on this installation. It can therefore not have any upgrade will take place.</p>');
define('sync_error_sync_missing_keys',					'<p>The response from the syncData server is incomplete, it does not include all the expected keys!</p>');
define('sync_error_sync_missing_params',				'<p>The request is incomplete, it was not passed all the required parameters!</p>');
define('sync_error_sync_response_invalid',			'<p>The syncData server did not respond in the expected form, the message is:<br />%s</p>');
define('sync_error_sync_server_inactive',				'<p>The syncData server isn´t active.</p>');
define('sync_error_template_error',							'<p>Error when running the template <b>%s</b>:</p><p>%s</p>');

define('sync_header_backup',										'Backup of data');
define('sync_header_backup_continue',						'Continue the backup of data ...');
define('sync_header_backup_finished',						'Backup of data finished!');
define('sync_header_backup_new',								'Create a new backup of data');
define('sync_header_backup_update',							'Update the backup of data');
define('sync_header_cfg',												'Settings');
define('sync_header_cfg_description',						'Explanation');
define('sync_header_cfg_identifier',						'Setting');
define('sync_header_cfg_value',									'Value');
define('sync_header_restore',										'Start restore');
define('sync_header_restore_continue',					'Continue the restore ...');
define('sync_header_restore_finished',					'Restore finished!');
define('sync_header_update_continue',						'Continue the update ...');
define('sync_header_update_finished',						'Update finished');

define('sync_hint_archive_name',								'');
define('sync_hint_backup_type_select',					'');
define('sync_hint_backup_select',								'');
define('sync_hint_restore_select',							'');

define('sync_intro_backup',											'<p>Create a new backup or select a backup which will be updated.</p>');
define('sync_intro_backup_new',									'<p>Select the type of data backup and give the archive a name.</p>');
define('sync_intro_backup_update',							'<p>Check that the correct backup archive will be updated and give the update archive a name.</p>');
define('sync_intro_cfg',												'<p>Edit the settings for <b>%s</b>.</p>');
define('sync_intro_restore',										'<p>Select the backup from which will be used for data recovery.</p>');
define('sync_intro_restore_info',								'<p>Please check! Is the selected backup of data right one -  should it be restored?</p><p>Define the settings for restore and then start the process.</p>');

define('sync_label_archive_id',									'Archive ID');
define('sync_label_archive_info',								'Archive information');
define('sync_label_archive_name',								'Name of the archive');
define('sync_label_archive_number',							'Archive number');
define('sync_label_archive_type',								'Archive type');
define('sync_label_backup_type_select',					'Select backup type');
define('sync_label_backup_select',							'Select backup');
define('sync_label_cfg_auto_exec_msec',					'AutoExec in milliseconds');
define('sync_label_cfg_filemtime_diff_allowed',	'Allowed time difference');
define('sync_label_cfg_ignore_directories',			'Ignored directories');
define('sync_label_cfg_ignore_file_extensions',	'Ignored file extensions');
define('sync_label_cfg_ignore_tables',					'Ignored MySQL tables');
define('sync_label_cfg_limit_execution_time',		'Limit of execution time (script)');
define('sync_label_cfg_memory_limit',						'Memory limit');
define('sync_label_cfg_max_execution_time',			'Max execution time (script)');
define('sync_label_cfg_server_active',					'syncData Server');
define('sync_label_cfg_server_archive_id',			'Archive ID for synchronization');
define('sync_label_cfg_server_url',							'syncData Server URL');
define('sync_label_files',											'Files');
define('sync_label_restore',										'Restore');
define('sync_label_restore_delete',							'Delete');
define('sync_label_restore_delete_files',				'delete existing files which are not included in the archive');
define('sync_label_restore_delete_tables',			'delete existing tables which are not included in the archive');
define('sync_label_restore_ignore',							'Ignore');
define('sync_label_restore_ignore_config',			'config.php');
define('sync_label_restore_ignore_htaccess',		'.htaccess');
define('sync_label_restore_mode',								'Mode');
define('sync_label_restore_mode_time_size',			'replace changed tables and files (check date & size)');
define('sync_label_restore_mode_binary',				'replace changed tables and files (binary comparison, <i>very slow!</i>)');
define('sync_label_restore_mode_replace_all',		'replace all tables and files');
define('sync_label_restore_replace',						'Search & Replace');
define('sync_label_restore_replace_prefix',			'update TABLE_PREFIX in MySQL tables');
define('sync_label_restore_replace_url',				'update WB_URL in MySQL tables');
define('sync_label_restore_select',							'Choose a restore!');
define('sync_label_status',											'Status');
define('sync_label_tables',											'MySQL tables');
define('sync_label_timestamp',									'Timestamp');
define('sync_label_total_files',								'Total files');
define('sync_label_total_size',									'Total size');
define('sync_label_wb_path',										'WB_PATH');
define('sync_label_wb_url',											'WB_URL');

define('sync_msg_auto_exec_msec',								'<p style="color:red;"><em>AutoExec is active. The process will continue automatically in %d milliseconds.</em></p>');
define('sync_msg_backup_finished',							'<p>The backup was completed successfully.</p><p>There were <b>%s</b> files backed up with a circumference of <b>%s</b>.</p><p>See the full archive:<br /><a href="%s">%s</a>.');
define('sync_msg_backup_to_be_continued',				'<p>The backup isn´t complete because not all files could be secured within the maximum execution time for PHP scripts from <b>%s seconds</b>.</p><p>Until now, <b>%s</b> files backed up with a circumference of <b>%s</b>.</p><p>Please click "Continue ..." to proceed the backup.</p>%s');
define('sync_msg_backup_running',								'<p>The backup runs.</p><p>Please don´t close this window and <b>wait for the status message by syncData you will get after max. %s seconds!</b></p>');
define('sync_msg_cfg_id_updated',								'<p>The configuration record with the identifier <b>%s</b> has been updated.</p>');
define('sync_msg_install_droplets_failed',			'The installation of the Droplets is unfotunately failed for %s - Error message: %s');
define('sync_msg_install_droplets_success',			'The Droplets for %s were successfully installed. You will find further informations about the use of Droplets in the dokumentation!');
define('sync_msg_invalid_email',								'<p>The e-mail address <b>%s</b> is not valid, please check your spelling.</p>');
define('sync_msg_nothing_to_do',								'<p>There is nothing to do - task completed.</p>');
define('sync_msg_no_backup_files_in_dir',				'<p>No backups were found in the directory <b>%s</b>, which can be used for a restore.</p><p></p>Transfer the archive files manually via FTP to the directory <b>%s</b> and you call this dialogue again.</p>');
define('sync_msg_no_backup_file_for_process',		'<p>The system got no backup of data that could be recovered.</p>');
define('sync_msg_restore_running',							'<p>The data restore runs.</p><p>Please don´t close this window and <b>wait for the status message by syncData you will get after max. %s seconds!</b></p>');
define('sync_msg_restore_finished',							'<p>The data restore is complete.</p><p>tables:<br /><ul><li>deleted: %d (%s)</li><li>added: %d (%s)</li><li>changed: %d (%s)</li></ul></p><p>files:<br /><ul><li>deleted: %d (%s)</li><li>added: %d (%s)</li><li>changed: %d (%s)</li></ul></p>');
define('sync_msg_restore_interrupted',  				'<p>The data restore isn´t complete because not all files could be checked and restored within the maximum execution time for PHP scripts from <b>%s seconds</b>.</p><p>intermediate result of tables:<br /><ul><li>deleted: %d (%s)</li><li>added: %d (%s)</li><li>changed: %d (%s)</li></ul></p><p>intermediate result of files:<br /><ul><li>deleted: %d (%s)</li><li>added: %d (%s)</li><li>changed: %d (%s)</li></ul></p>%s');
define('sync_msg_sync_connect_failed',					'<p>Could not connect to the syncData server.</p><p><a href="%s">Trying to connect again!</a>.</p>');
define('sync_msg_update_finished',							'<p>The update was completed successfully..</p><p>There were <b>%s</b> files backed up with a circumference of <b>%s</b>.</p><p>See the full archive:<br /><a href="%s">%s</a>.');
define('sync_msg_update_running',								'<p>The update runs.</p><p>Please don´t close this window and <b>wait for the status message by syncData you will get after max. %s seconds!</b></p>');
define('sync_msg_update_to_be_continued',				'<p>The update isn´t complete because not all files could be secured within the maximum execution time for PHP scripts from <b>%s seconds</b>.</p><p>Until now, <b>%s</b> files updated with a circumference of <b>%s</b>.</p><p>Please click "Continue ..." to proceed the update.</p>%s');

define('sync_protocol_file_add',								'The file %s was added.');
define('sync_protocol_file_delete',							'The file %s was deleted.');
define('sync_protocol_file_replace',						'The file %s has been replaced.');
define('sync_protocol_table_add',								'The table %s was added.');
define('sync_protocol_table_delete',						'The table %s has been deleted');
define('sync_protocol_table_ignored',						'The table %s has been ignored.');
define('sync_protocol_table_replace',						'The table %s has been replaced.');

define('sync_str_new_backup',										'- create new backup -');
define('sync_str_backup_default_name',					'backup of data from %s');
define('sync_str_restore_select',								'- select restore -');
define('sync_str_undefined',										'- undefined -');
define('sync_str_update_default_name',					'update from %s');

define('sync_tab_about',												'?');
define('sync_tab_backup',												'Backup');
define('sync_tab_cfg',													'Settings');
define('sync_tab_restore',											'Restore');

define('sync_type_complete',										'complete (database and files)');
define('sync_type_mysql',												'only database (MySQL)');
define('sync_type_files',												'only files');

// definitions used by class.tools.php --> kitTools

define('tool_error_link_by_page_id', 						'<p>Couldn´t read the file name for the PAGE ID <strong>%d</strong> from the settings of installation (LEPTON CMS).</p>');
define('tool_error_link_row_empty', 						'<p>There is no entry for the PAGE ID <strong>%d</strong> in the settings of installation (LEPTON CMS).</p>');

?>