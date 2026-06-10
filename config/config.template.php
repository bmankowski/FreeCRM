<?php
/* * *******************************************************************************
 * Install-time template for config.inc.php. Placeholders are replaced by
 * Install_ConfigFileUtils_Model during installation. Do not deploy real secrets here.
 * ****************************************************************************** */

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
@ini_set('memory_limit', '512M');
@ini_set('session.gc_maxlifetime', '21600');

$CALENDAR_DISPLAY = 'true';
$WORLD_CLOCK_DISPLAY = 'true';
$CALCULATOR_DISPLAY = 'true';
$CHAT_DISPLAY = 'true';
$USE_RTE = 'true';

$PORTAL_URL = 'https://portal.yetiforce.com';
$HELPDESK_SUPPORT_NAME = 'your-support name';
$HELPDESK_SUPPORT_EMAIL_REPLY = '';

// Report Issue widget: config/modules/ReportIssue.php

$dbconfig['db_server'] = '_DBC_SERVER_';
$dbconfig['db_port'] = '_DBC_PORT_';
$dbconfig['db_username'] = '_DBC_USER_';
$dbconfig['db_password'] = '_DBC_PASS_';
$dbconfig['db_name'] = '_DBC_NAME_';
$dbconfig['db_type'] = '_DBC_TYPE_';
$dbconfig['db_status'] = '_DB_STAT_';

$dbconfig['db_hostname'] = '_DBC_SERVER_' . ':' . '_DBC_PORT_';

$host_name = $dbconfig['db_hostname'];

$site_URL = '_SITE_URL_';

$cache_dir = '_VT_CACHEDIR_';
$tmp_dir = '_VT_TMPDIR_';
$import_dir = '_VT_CACHEDIR_' . 'import/';
$upload_dir = '_VT_CACHEDIR_' . 'upload/';

$upload_maxsize = 52428800;
$allow_exports = 'all';

$upload_badext = array('php', 'php3', 'php4', 'php5', 'pl', 'cgi', 'py', 'asp', 'cfm', 'js', 'vbs', 'html', 'htm', 'exe', 'bin', 'bat', 'sh', 'dll', 'phps', 'phtml', 'xhtml', 'rb', 'msi', 'jsp', 'shtml', 'sth', 'shtm');

$list_max_entries_per_page = '20';
$limitpage_navigation = '5';
$history_max_viewed = '5';

$default_module = 'Home';
$default_action = 'index';

$default_theme = 'softed';

$default_user_name = '';

$currency_name = '_MASTER_CURRENCY_';

$default_charset = '_VT_CHARSET_';

$default_language = '_LANG_';

$translation_string_prefix = false;

$cache_tab_perms = true;

$display_empty_home_blocks = false;

$disable_stats_tracking = false;

$application_unique_key = '_VT_APP_UNIQKEY_';

// FreeCRM password pepper. Used as the HMAC-SHA-256 key applied to every
// user password before Argon2id hashing. Treat it like a database-encryption
// key: rotating it invalidates every login hash; losing it is unrecoverable.
// The installer auto-generates a 256-bit value (64 hex chars).
$user_password_pepper = '_FREECRM_PWD_PEPPER_';

$listview_max_textlength = 40;

$php_max_execution_time = 0;

$default_timezone = '_TIMEZONE_';

if (isset($default_timezone) && function_exists('date_default_timezone_set')) {
	@date_default_timezone_set($default_timezone);
}

$title_max_length = 60;

$href_max_length = 35;

$breadcrumbs = true;

$breadcrumbs_separator = '>';

$MINIMUM_CRON_FREQUENCY = 1;

$session_regenerate_id = false;

$davStorageDir = 'storage/Files';
$davHistoryDir = 'storage/FilesHistory';

$systemMode = 'prod';

$forceSSL = false;

$listMaxEntriesMassEdit = 500;

$backgroundClosingModal = true;

$csrfProtection = true;

$isActiveSendingMails = true;

$unblockedTimeoutCronTasks = true;

$maxExecutionCronTime = 3600;

$langInLoginView = false;

$layoutInLoginView = false;

$defaultLayout = 'basic';

$forceRedirect = true;
