#!/usr/bin/env php
<?php
/**
 * FreeCRM - Smoke checks after Kandydaci → Candidates migration.
 *
 * Run: docker compose exec -T app php bin/check_candidates_migration.php
 */

declare(strict_types=1);

if (!defined('ROOT_DIRECTORY')) {
	define('ROOT_DIRECTORY', dirname(__DIR__));
}

require ROOT_DIRECTORY . '/vendor/autoload.php';
require ROOT_DIRECTORY . '/vendor/yiisoft/yii2/Yii.php';
require ROOT_DIRECTORY . '/config/api.php';
require ROOT_DIRECTORY . '/config/config.php';

\App\Core\AppConfig::init($API_CONFIG);
\App\Core\Loader::register();
\App\Db\Db::$connectCache = \App\Core\AppConfig::performance('ENABLE_CACHING_DB_CONNECTION');

$db = \App\Db\Db::getInstance();
$errors = [];

$tabName = $db->createCommand('SELECT name FROM vtiger_tab WHERE tabid = 121')->queryScalar();
if ($tabName !== 'Candidates') {
	$errors[] = "vtiger_tab.name expected Candidates, got: $tabName";
}

if ($db->getSchema()->getTableSchema('u_yf_candidates', true) === null) {
	$errors[] = 'Table u_yf_candidates missing';
}
if ($db->getSchema()->getTableSchema('u_yf_kandydaci', true) !== null) {
	$errors[] = 'Legacy table u_yf_kandydaci still exists';
}

$oldSetype = (int) $db->createCommand(
	"SELECT COUNT(*) FROM vtiger_crmentity WHERE setype = 'Kandydaci' AND deleted = 0"
)->queryScalar();
if ($oldSetype > 0) {
	$errors[] = "Found $oldSetype active records with setype=Kandydaci";
}

$fieldCount = (int) $db->createCommand(
	"SELECT COUNT(*) FROM vtiger_field WHERE tabid = 121 AND tablename = 'u_yf_candidates'"
)->queryScalar();
if ($fieldCount < 20) {
	$errors[] = "Expected many fields on u_yf_candidates, got $fieldCount";
}

$legacyColumns = (int) $db->createCommand(
	"SELECT COUNT(*) FROM vtiger_cvcolumnlist WHERE columnname LIKE '%u_yf_kandydaci%'"
)->queryScalar();
if ($legacyColumns > 0) {
	$errors[] = "Found $legacyColumns cvcolumnlist entries still referencing u_yf_kandydaci";
}

$recordCount = (int) $db->createCommand('SELECT COUNT(*) FROM u_yf_candidates')->queryScalar();
echo "Candidates records: $recordCount\n";

if (!is_file(ROOT_DIRECTORY . '/user_privileges/tabdata.php')) {
	$errors[] = 'user_privileges/tabdata.php missing — run bin/regenerate_user_privileges.php';
}
if (!is_file(ROOT_DIRECTORY . '/user_privileges/menu_0.php')) {
	$errors[] = 'user_privileges/menu_0.php missing — run bin/regenerate_user_privileges.php';
}
if (!is_file(ROOT_DIRECTORY . '/user_privileges/locks.php')) {
	$errors[] = 'user_privileges/locks.php missing — run bin/regenerate_user_privileges.php';
}
if (!is_file(ROOT_DIRECTORY . '/user_privileges/watchdogModule.php')) {
	$errors[] = 'user_privileges/watchdogModule.php missing — run bin/regenerate_user_privileges.php';
}
if (!is_file(ROOT_DIRECTORY . '/user_privileges/moduleHierarchy.php')) {
	$errors[] = 'user_privileges/moduleHierarchy.php missing — run bin/regenerate_user_privileges.php';
}

foreach (['vtiger_candidate_status', 'vtiger_availability', 'vtiger_work_time_type', 'vtiger_application_source'] as $picklistTable) {
	if ($db->getSchema()->getTableSchema($picklistTable, true) === null) {
		$errors[] = "Picklist table $picklistTable missing — run migrations/Users/m260609_000006_rename_candidates_picklists.php";
	}
}

$oldEmailTemplates = (int) $db->createCommand(
	"SELECT COUNT(*) FROM u_yf_emailtemplates WHERE module = 'Kandydaci'"
)->queryScalar();
if ($oldEmailTemplates > 0) {
	$errors[] = "Found $oldEmailTemplates email templates still using module=Kandydaci";
}

$oldFieldModuleRel = (int) $db->createCommand(
	"SELECT COUNT(*) FROM vtiger_fieldmodulerel WHERE module = 'Kandydaci' OR relmodule = 'Kandydaci'"
)->queryScalar();
if ($oldFieldModuleRel > 0) {
	$errors[] = "Found $oldFieldModuleRel vtiger_fieldmodulerel row(s) still referencing Kandydaci";
}

if (!is_dir(ROOT_DIRECTORY . '/src/Modules/Candidates')) {
	$errors[] = 'src/Modules/Candidates/ directory missing';
}
if (is_dir(ROOT_DIRECTORY . '/src/Modules/Kandydaci')) {
	$errors[] = 'Legacy src/Modules/Kandydaci/ directory still present';
}

$legacyStorageDirs = [
	'storage/MultiAttachment/Kandydaci',
	'storage/MultiImage/Kandydaci',
];
foreach ($legacyStorageDirs as $dir) {
	if (is_dir(ROOT_DIRECTORY . '/' . $dir)) {
		$errors[] = "Legacy storage directory still present: $dir";
	}
}

$legacyCvPaths = (int) $db->createCommand(
	"SELECT COUNT(*) FROM u_yf_candidates WHERE cv_img_file LIKE '%Kandydaci%'"
)->queryScalar();
if ($legacyCvPaths > 0) {
	$errors[] = "Found $legacyCvPaths cv_img_file value(s) still referencing Kandydaci in path";
}

$legacyElementCodes = (int) $db->createCommand(
	"SELECT COUNT(*) FROM u_yf_templateelements WHERE code LIKE 'kandydaci_%'"
)->queryScalar();
if ($legacyElementCodes > 0) {
	$errors[] = "Found $legacyElementCodes template element(s) with kandydaci_* code";
}

$legacyWidgetTypes = (int) $db->createCommand(
	"SELECT COUNT(*) FROM vtiger_widgets WHERE tabid = 121 AND type LIKE 'Kandydaci%'"
)->queryScalar();
if ($legacyWidgetTypes > 0) {
	$errors[] = "Found $legacyWidgetTypes vtiger_widgets row(s) with legacy Kandydaci* type on tabid=121";
}

if ($errors === []) {
	echo "OK — all migration checks passed.\n";
	exit(0);
}

foreach ($errors as $error) {
	fwrite(STDERR, "ERROR: $error\n");
}
exit(1);
