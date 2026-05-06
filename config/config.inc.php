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

$dbconfig['db_server'] = 'db';
$dbconfig['db_port'] = '3306';
$dbconfig['db_username'] = 'freecrm';
$dbconfig['db_password'] = 'freecrm';
$dbconfig['db_name'] = 'freecrm';
$dbconfig['db_type'] = 'mysql';
$dbconfig['db_status'] = 'true';

$dbconfig['db_hostname'] = 'db' . ':' . '3306';

$host_name = $dbconfig['db_hostname'];

$site_URL = 'http://localhost/';

$cache_dir = 'cache/';
$tmp_dir = 'cache/images/';
$import_dir = 'cache/' . 'import/';
$upload_dir = 'cache/' . 'upload/';

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

$currency_name = 'Euro';

$default_charset = 'UTF-8';

$default_language = 'pl_pl';

$translation_string_prefix = false;

$cache_tab_perms = true;

$display_empty_home_blocks = false;

$disable_stats_tracking = false;

$application_unique_key = 'ad4eebb31c2a814f8b37e2cd41fdf7c0facfe396';

$listview_max_textlength = 40;

$php_max_execution_time = 0;

$default_timezone = 'Europe/London';

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
