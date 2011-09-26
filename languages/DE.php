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

define('sync_btn_abort',												'Abbruch');
define('sync_btn_ok',														'Übernehmen');
define('sync_btn_continue',											'Fortsetzen ...');
define('sync_btn_start',												'Starten ...');

define('sync_cfg_currency',											'%s €');
define('sync_cfg_date_separator',								'.'); 
define('sync_cfg_date_str',											'd.m.Y');
define('sync_cfg_datetime_str',									'd.m.Y H:i');
define('sync_cfg_day_names',										"Sonntag, Montag, Dienstag, Mittwoch, Donnerstag, Freitag, Samstag");
define('sync_cfg_decimal_separator',          	',');
define('sync_cfg_month_names',									"Januar, Februar, März, April, Mai, Juni, Juli, August, September, Oktober, November, Dezember");
define('sync_cfg_thousand_separator',						'.');
define('sync_cfg_time_long_str',								'H:i:s');
define('sync_cfg_time_str',											'H:i');
define('sync_cfg_time_zone',										'Europe/Berlin');
define('sync_cfg_title',												'Herr,Frau');

define('sync_desc_cfg_auto_exec_msec',					'Die Wartezeit in Millisekunden, bis syncData einen unterbrochenen Prozess automatisch fortsetzt. Beträgt der Wert <b>0</b>, wird die automatische Fortsetzung ausgeschaltet. Der Standardwert ist <b>5000</b> Millisekunden.');
define('sync_desc_cfg_filemtime_diff_allowed',	'Erlaubte Abweichung beim <b>filemtime()</b> Vergleich in Sekunden, der Standardwert ist 1 Sekunde.');
define('sync_desc_cfg_limit_execution_time',		'Limit der Ausführungsdauer in Sekunden. Bei Erreichen des Wertes bricht das Script die Ausführung ab, um ein Überschreiten der <b>maximalen Ausführungsdauer</b> zu verhindern.');
define('sync_desc_cfg_max_execution_time',			'Maximale Ausführungsdauer der Scripts in Sekunden. Der Standardwert beträgt 30 Sekunden');
define('sync_desc_cfg_memory_limit',						'Maximaler Speicher (RAM), der syncData für die Ausführung der Scripts zur Verfügung steht. Die Angabe erfolgt in <b>Bytes</b> als Integer Wert oder als <a href="http://it.php.net/manual/de/faq.using.php#faq.using.shorthandbytes" target="_blank">abgekürzter Byte-Wert</a>, z.B. "256M".');
define('sync_desc_cfg_ignore_directories',			'Verzeichnisse, die von syncData grundsätzlich ignoriert werden sollen.');
define('sync_desc_cfg_ignore_file_extensions',	'Dateien mit den angegebenen Endungen werden von syncData grundsätzlich ignoriert. Trennen Sie die Einträge mit einem Komma.');
define('sync_desc_cfg_ignore_tables',						'MySQL Tabellen, die von syncData grundsätzlich ignoriert werden sollen. Achten Sie darauf, dass Sie die Tabellen <b>ohne TABLE_PREFIX</b> (lep_, wb_ o.ä.) angeben.');
define('sync_desc_cfg_server_active',						'Geben Sie diese syncData Installation als Server frei, wenn sich andere syncData Clients mit dieser Installation synchronisieren sollen.<br />0 = Server AUS, 1 = Server EIN');
define('sync_desc_cfg_server_archive_id',				'Wählen Sie die <b>ID</b> des Sicherungsarchiv aus, das für eine Synchronisation verwendet werden soll.');
define('sync_desc_cfg_server_url',							'Wenn Sie diese syncData Installation als <b>Client</b> verwenden, geben Sie hier die vollständige URL des syncData <b>Server</b> an.');

define('sync_error_allow_url_fopen',						'<p>syncData erfordert die Einstellung <b>allow_url_fopen = 1</b> in der <b>php.ini</b> für die Synchronisation.</p>');
define('sync_error_archive_id_invalid',					'<p>Zu dem Archiv mit der ID <b>%s</b> wurde kein Datensatz gefunden!</p>');
define('sync_error_archive_missing_ini',				'<p>Das Archiv <b>%s</b> ist kein gültiges syncData Archiv, die Datei sync_data.ini fehlt.</p>');
define('sync_error_backup_archive_invalid',			'<p>Es wurde kein gültiges Backup Archiv angegeben!</p>');
define('sync_error_cfg_id',											'<p>Der Konfigurationsdatensatz mit der <b>ID %05d</b> konnte nicht ausgelesen werden!</p>');
define('sync_error_cfg_name',										'<p>Zu dem Bezeichner <b>%s</b> wurde kein Konfigurationsdatensatz gefunden!</p>');
define('sync_error_copy_file',									'<p>Die Datei <b>%s</b> konnte nicht nach %s kopiert werden!</p>');
define('snyc_error_dir_not_readable',						'<p>Das Verzeichnis <b>%s</b> ist nicht lesbar!</p>');
define('sync_error_file_copy',									'<p>Die Datei <b>%s</b> konnte nicht nach <b>%s</b> kopiert werden.</p>');
define('sync_error_file_delete',								'<p>Die Datei <b>%s</b> konnte nicht gelöscht werden.</p>');
define('sync_error_file_get_contents',					'<p>Die (<i>ferne</i>) Datei <b>%s</b> konnte nicht gelesen werden.</p>');
define('sync_error_file_handle',								'<p>Es konnte kein Datei Handle für <b>%s</b> erzeugt werden!</p>');
define('sync_error_file_list_invalid',					'<p>Die Dateiliste ist ungültig.</p>'); 
define('sync_error_file_list_no_files',					'<p>Die Dateiliste enthält keine Dateien für ein Restore!</p>');
define('sync_error_file_list_no_mysql_files',		'<p>Die Dateiliste enthält keine MySQL Dateien!</p>');
define('sync_error_file_not_exists',						'<p>Die Datei <b>%s</b> existiert nicht!</p>');
define('sync_error_file_open',									'<p>Die Datei <b>%s</b> konnte nicht geöffnet werden!</p>');
define('sync_error_file_put_contents',					'<p>Die Datei <b>%s</b> konnte nicht geschrieben werden!</p>');
define('sync_error_file_read',									'<p>Die Datei <b>%s</b> konnte nicht gelesen werden!</p>');
define('sync_error_file_rename',								'<p>Die Datei <b>%s</b> konnte nicht umbenannt werden!</p>'); 
define('sync_error_file_write',									'<p>Fehler beim Schreiben in die Datei <b>%s</b>.</p>');
define('sync_error_job_id_invalid',							'<p>Es wurde kein syncData Job mit der ID <b>%s</b> gefunden!</p>');
define('sync_error_mkdir',											'<p>Das Verzeichnis <b>%s</b> konnte nicht angelegt werden!</p>');
define('sync_error_param_missing_server',				'<p>Dem Droplet <b>sync_client</b> fehlt der Parameter <b>server</b> mit der entfernten Adresse, auf die zugriffen werden soll!</p>');
define('sync_error_preset_not_exists',					'<p>Das Presetverzeichnis <b>%s</b> existiert nicht, die erforderlichen Templates können nicht geladen werden!</p>');
define('sync_error_rmdir',											'<p>Das Verzeichnis <b>%s</b> konnte nicht gelöscht werden!</p>');
define('sync_error_status_unknown',							'<p>Unbekannter Status. Bitte informieren Sie den Support.</p>');
define('sync_error_sync_action_forbidden',			'<p>Ungültiger syncData Aufruf! Sprechen Sie den Server mit den vorgeschriebenen Parametern an!</p>'); 
define('sync_error_sync_archive_filesize',			'<p>Die Dateigröße des Archiv %s konnte nicht ermittelt werden.</p>');
define('sync_error_sync_archive_file_get_md5',	'<p>Es konnte keine MD5 Prüfsumme für das Archiv %s ermittelt werden!</p>');
define('sync_error_sync_archive_file_missing',	'<p>Das Archiv %s wurde nicht gefunden!</p>');
define('sync_error_sync_archive_id_invalid',		'<p>Zu der syncData Archive ID <b>%s</b> wurde kein gültiger Job gefunden!</p>');
define('sync_error_sync_archive_id_missing',		'<p>Der syncData Server ist aktiv, es wurde jedoch keine Archiv ID für die Synchronisation festgelegt.</p>');
define('sync_error_sync_data_corrupt',					'<p>Der syncData Client kann die vom Server gelieferten Daten für die Archiv ID <b>%s</b> nicht korrekt zuordnen.</p>');
define('sync_error_sync_data_ini_missing',			'<p>Die Archiv Beschreibung <b>sync_data.ini</b> wurde nicht gefunden!</p>');
define('sync_error_sync_download_archive_file',	'<p>Fehler beim Download des Archiv <b>%s</b> vom syncData Server!</p>');
define('sync_error_sync_md5_checksum_differ',		'<p>Die für das Archiv <b>%s</b> ermittelte MD5 Prüfsumme weicht von dem Vorgabewert ab.</p><p>Das Archiv ist ungültig und wird verworfen.</p>');
define('sync_error_sync_missing_initial_restore','<p>Die Basissynchronisation für das Archiv mit der ID <b>%s</b> wurde auf dieser Installation noch nicht durchgeführt, es kann keine Aktualisierung durchgeführt werden!</p>');
define('sync_error_sync_missing_keys',					'<p>Die Antwort des syncData Servers ist unvollständig, es sind nicht alle erwarteten Schlüssel enthalten!</p>');
define('sync_error_sync_missing_params',				'<p>Die Anfrage ist unvollständig, es wurden nicht alle erforderlichen Parameter übergeben!</p>');
define('sync_error_sync_response_invalid',			'<p>Der syncData Server hat nicht in der erwarteten Form geantwortet, die Meldung lautet:<br />%s</p>');
define('sync_error_sync_server_inactive',				'<p>Der syncData Server ist nicht aktiviert!</p>');
define('sync_error_template_error',							'<p>Fehler bei der Ausführung des Template <b>%s</b>:</p><p>%s</p>');

define('sync_header_backup',										'Datensicherung');
define('sync_header_backup_continue',						'Datensicherung fortsetzen');
define('sync_header_backup_finished',						'Datensicherung beendet');
define('sync_header_backup_new',								'Neue Datensicherung erstellen');
define('sync_header_backup_update',							'Datensicherung aktualisieren');
define('sync_header_cfg',												'Einstellungen');
define('sync_header_cfg_description',						'Erläuterung');
define('sync_header_cfg_identifier',						'Einstellung'); 
define('sync_header_cfg_value',									'Wert');
define('sync_header_restore',										'Rücksicherung durchführen');
define('sync_header_restore_continue',					'Rücksicherung fortsetzen');
define('sync_header_restore_finished',					'Rücksicherung beendet');
define('sync_header_update_continue',						'Aktualisierung fortsetzen');
define('sync_header_update_finished',						'Aktualisierung beendet');

define('sync_hint_archive_name',								'');
define('sync_hint_backup_type_select',					'');
define('sync_hint_backup_select',								'');
define('sync_hint_restore_select',							'');

define('sync_intro_backup',											'<p>Erstellen Sie ein neues Backup oder wählen Sie ein Backup aus, das aktualisiert werden soll.</p>');
define('sync_intro_backup_new',									'<p>Wählen Sie die Art der Datensicherung aus und geben Sie dem Archiv einen Namen.</p>');
define('sync_intro_backup_update',							'<p>Prüfen Sie, ob das richtige Backup Archiv aktualisiert wird und geben Sie dem Update Archiv einen Namen.</p>');
define('sync_intro_cfg',												'<p>Bearbeiten Sie die Einstellungen für <b>%s</b>.</p>');
define('sync_intro_restore',										'<p>Wählen Sie die Datensicherung aus, die für die Herstellung von Daten verwendet werden soll.</p>');
define('sync_intro_restore_info',								'<p>Bitte prüfen Sie, ob es sich um die gewünschte Datensicherung handelt.</p><p>Legen Sie die Art der Rücksicherung fest und starten Sie danach den Restore.</p>');

define('sync_label_archive_id',									'Archiv ID');
define('sync_label_archive_info',								'Archiv Information');
define('sync_label_archive_name',								'Name für das Archiv');
define('sync_label_archive_number',							'Archiv Nummer');
define('sync_label_archive_type',								'Archiv Typ');
define('sync_label_backup_type_select',					'Sicherungstyp auswählen');
define('sync_label_backup_select',							'Backup auswählen');
define('sync_label_cfg_auto_exec_msec',					'AutoExec in Millisekunden');
define('sync_label_cfg_filemtime_diff_allowed',	'Erlaubte Zeitdifferenz');
define('sync_label_cfg_ignore_directories',			'Ignorierte Verzeichnisse');
define('sync_label_cfg_ignore_file_extensions',	'Ignorierte Dateiendungen');
define('sync_label_cfg_ignore_tables',					'Ignorierte MySQL Tabellen');
define('sync_label_cfg_limit_execution_time',		'Limit Script Ausführungsdauer');
define('sync_label_cfg_memory_limit',						'Speicherbegrenzung');
define('sync_label_cfg_max_execution_time',			'Max. Script Ausführungsdauer');
define('sync_label_cfg_server_active',					'syncData Server');
define('sync_label_cfg_server_archive_id',			'Archiv ID für die Synchronisation'); 
define('sync_label_cfg_server_url',							'syncData Server URL');
define('sync_label_files',											'Dateien');
define('sync_label_restore',										'Rücksicherung');
define('sync_label_restore_delete',							'Löschen');
define('sync_label_restore_delete_files',				'vorhandene Dateien löschen, die nicht im Archiv enthalten sind'); 
define('sync_label_restore_delete_tables',			'vorhandene Tabellen löschen, die nicht im Archiv enthalten sind');
define('sync_label_restore_ignore',							'Ignorieren');
define('sync_label_restore_ignore_config',			'config.php');
define('sync_label_restore_ignore_htaccess',		'.htaccess');
define('sync_label_restore_mode',								'Modus');
define('sync_label_restore_mode_time_size',			'geänderte Tabellen und Dateien ersetzen (Datum & Größe prüfen)');
define('sync_label_restore_mode_binary',				'geänderte Tabellen und Dateien ersetzen (binärer Vergleich, <i>sehr langsam!</i>)');
define('sync_label_restore_mode_replace_all',		'alle Tabellen und Dateien ersetzen');
define('sync_label_restore_replace',						'Suchen & Ersetzen');
define('sync_label_restore_replace_prefix',			'TABLE_PREFIX in MySQL Tabellen aktualisieren');
define('sync_label_restore_replace_url',				'WB_URL in MySQL Tabellen aktualisieren'); 
define('sync_label_restore_select',							'Rücksicherung auswählen');
define('sync_label_status',											'Status');
define('sync_label_tables',											'MySQL Tabellen');
define('sync_label_timestamp',									'Zeitstempel');
define('sync_label_total_files',								'Dateien insgesamt');
define('sync_label_total_size',									'Größe insgesamt');
define('sync_label_wb_path',										'WB_PATH');
define('sync_label_wb_url',											'WB_URL');

define('sync_msg_auto_exec_msec',								'<p style="color:red;"><em>AutoExec ist aktiv, der Vorgang wird in %d Millisekunden automatisch fortgesetzt.</em></p>');
define('sync_msg_backup_finished',							'<p>Die Datensicherung wurde erfolgreich abgeschlossen.</p><p>Es wurden <b>%s</b> Dateien mit einem Umfang von <b>%s</b> gesichert.</p><p>Sie finden das vollständige Archiv unter:<br /><a href="%s">%s</a>.');
define('sync_msg_backup_to_be_continued',				'<p>Die Datensicherung konnte nicht abgeschlossen werden, da nicht alle Dateien innerhalb der maximalen Ausführungszeit für PHP Scripte von <b>%s Sekunden</b> gesichert werden konnten.</p><p>Bis jetzt wurden <b>%s</b> Dateien mit einem Umfang von <b>%s</b> gesichert.</p><p>Bitte klicken Sie auf "Fortsetzen ..." um die Datensicherung weiter auszuführen.</p>%s');
define('sync_msg_backup_running',								'<p>Die Datensicherung wird ausgeführt.</p><p>Schliessen Sie dieses Fenster nicht und <b>warten Sie die Statusmeldung durch syncData nach max. %s Sekunden ab</b>.</p>');
define('sync_msg_cfg_id_updated',								'<p>Der Konfigurationsdatensatz mit dem Bezeichner <b>%s</b> wurde aktualisiert.</p>');
define('sync_msg_install_droplets_failed',			'Die Installation der Droplets fuer %s ist leider fehlgeschlagen, Fehlermeldung: %s');
define('sync_msg_install_droplets_success',			'Die Droplets fuer %s wurden erfolgreich installiert. Informationen zur Verwendung der Droplets finden Sie in der Dokumentation!');
define('sync_msg_invalid_email',								'<p>Die E-Mail Adresse <b>%s</b> ist nicht gültig, bitte prüfen Sie Ihre Eingabe.</p>');
define('sync_msg_nothing_to_do',								'<p>Es gibt nichts zu tun, Aktion beendet.</p>');
define('sync_msg_no_backup_files_in_dir',				'<p>Im Verzeichnis <b>%s</b> wurden keine Backups gefunden, die für eine Rücksicherung verwendet werden können.</p><p></p>Übertragen Sie die Archivdateien, die verwendet werden sollen, per FTP in das Verzeichnis <b>%s</b> und rufen Sie diesen Dialog anschließend erneut auf.</p>');
define('sync_msg_no_backup_file_for_process',		'<p>Es wurde keine Datensicherung übergeben, die wiederhergestellt werden kann.</p>');
define('sync_msg_restore_running',							'<p>Die Datenwiederherstellung wird ausgeführt.</p><p>Schliessen Sie dieses Fenster nicht und <b>warten Sie die Statusmeldung durch syncData nach max. %s Sekunden ab</b>.</p>');
define('sync_msg_restore_finished',							'<p>Die Datenwiederherstellung ist abgeschlossen.</p><p>Tabellen:<br /><ul><li>gelöscht: %d (%s)</li><li>hinzugefügt: %d (%s)</li><li>geändert: %d (%s)</li></ul></p><p>Dateien:<br /><ul><li>gelöscht: %d (%s)</li><li>hinzugefügt: %d (%s)</li><li>geändert: %d (%s)</li></ul></p>');
define('sync_msg_restore_interrupted',  				'<p>Die Datenwiederherstellung konnte nicht abgeschlossen werden, da nicht alle Dateien innerhalb der maximalen Ausführungszeit für PHP Scripte von <b>%s Sekunden</b> geprüft und wieder hergestellt werden konnten.</p><p>Zwischenstand Tabellen:<br /><ul><li>gelöscht: %d (%s)</li><li>hinzugefügt: %d (%s)</li><li>geändert: %d (%s)</li></ul></p><p>Zwischenstand Dateien:<br /><ul><li>gelöscht: %d (%s)</li><li>hinzugefügt: %d (%s)</li><li>geändert: %d (%s)</li></ul></p>%s');
define('sync_msg_sync_connect_failed',					'<p>Es konnte keine Verbindung zum syncData Server hergestellt werden.</p><p><a href="%s">Verbindungsversuch wiederholen!</a>.</p>');
define('sync_msg_update_finished',							'<p>Die Aktualisierung wurde erfolgreich abgeschlossen.</p><p>Es wurden <b>%s</b> Dateien mit einem Umfang von <b>%s</b> gesichert.</p><p>Sie finden das vollständige Archiv unter:<br /><a href="%s">%s</a>.');
define('sync_msg_update_running',								'<p>Die Aktualisierung wird ausgeführt.</p><p>Schliessen Sie dieses Fenster nicht und <b>warten Sie die Statusmeldung durch syncData nach max. %s Sekunden ab</b>.</p>');
define('sync_msg_update_to_be_continued',				'<p>Die Aktualisierung konnte nicht abgeschlossen werden, da nicht alle Dateien innerhalb der maximalen Ausführungszeit für PHP Scripte von <b>%s Sekunden</b> gesichert werden konnten.</p><p>Bis jetzt wurden <b>%s</b> Dateien mit einem Umfang von <b>%s</b> aktualisiert.</p><p>Bitte klicken Sie auf "Fortsetzen ..." um die Aktualisierung fortzusetzen.</p>%s');

define('sync_protocol_file_add',								'Die Datei %s wurde ersetzt.');
define('sync_protocol_file_delete',							'Die Datei %s wurde gelöscht.');
define('sync_protocol_file_replace',						'Die Datei %s wurde ersetzt.');
define('sync_protocol_table_add',								'Die Tabelle %s wurde hinzugefügt.');
define('sync_protocol_table_delete',						'Die Tabelle %s wurde gelöscht');
define('sync_protocol_table_ignored',						'Die Tabelle %s wurde ignoriert.'); 
define('sync_protocol_table_replace',						'Die Tabelle %s wurde ersetzt.');

define('sync_str_new_backup',										'- neues Backup erstellen -');
define('sync_str_backup_default_name',					'Datensicherung vom %s');
define('sync_str_restore_select',								'- Rücksicherung auswählen -');
define('sync_str_undefined',										'- nicht definiert -');
define('sync_str_update_default_name',					'Aktualisierung vom %s');

define('sync_tab_about',												'?');
define('sync_tab_backup',												'Backup');
define('sync_tab_cfg',													'Einstellungen');
define('sync_tab_restore',											'Restore');

define('sync_type_complete',										'Vollständig (Datenbank und Dateien)');
define('sync_type_mysql',												'nur die MySQL Datenbank');
define('sync_type_files',												'nur die Dateien');

// definitions used by class.tools.php --> kitTools

define('tool_error_link_by_page_id', 						'<p>Konnte den Dateinamen für die PAGE ID <strong>%d</strong> nicht aus den Einstellungen dieser Installation (LEPTON CMS) auslesen.</p>');
define('tool_error_link_row_empty', 						'<p>Es existiert kein Eintrag für die PAGE ID <strong>%d</strong> in den Einstellungen dieser Installation (LEPTON CMS).</p>');

?>