<?php
/**
 * Verify field_kind and storage_type backfill (Change 9).
 * Run: docker compose exec -T app php tests/field_metadata_backfill_smoke.php
 */

declare(strict_types=1);

define('ROOT_DIRECTORY', dirname(__DIR__));
define('REQUEST_MODE', 'TEST');

require_once ROOT_DIRECTORY . '/vendor/autoload.php';
require_once ROOT_DIRECTORY . '/vendor/yiisoft/yii2/Yii.php';
require_once ROOT_DIRECTORY . '/config/api.php';
require_once ROOT_DIRECTORY . '/config/config.php';
\App\Core\AppConfig::init($API_CONFIG);
\App\Core\Loader::register();
\App\EntryPoint\WebUI::initialize();
\App\User\CurrentUser::setContextUserId(1);

use App\Field\FieldKind;
use App\Field\StorageType;
use App\Modules\Base\Models\Field;
use App\Modules\Base\Models\Module;

$failures = 0;

function assertTrue(bool $cond, string $msg): void
{
	global $failures;
	if (!$cond) {
		echo "FAIL: $msg\n";
		++$failures;
	} else {
		echo "OK: $msg\n";
	}
}

function assertEquals(mixed $expected, mixed $actual, string $msg): void
{
	assertTrue($expected === $actual, $msg . " (expected {$expected}, got {$actual})");
}

$emptyKind = (int) (new \App\Db\Query())->from('vtiger_field')->where(['field_kind' => ''])->count();
$emptyStorage = (int) (new \App\Db\Query())->from('vtiger_field')->where(['storage_type' => ''])->count();
assertEquals(0, $emptyKind, 'no empty field_kind');
assertEquals(0, $emptyStorage, 'no empty storage_type');

$devRow = (new \App\Db\Query())->from('vtiger_field')
	->where(['fieldname' => 'developer_id', 'tabid' => 13])->one();
assertEquals('owner', $devRow['field_kind'] ?? '', 'HelpDesk developer_id field_kind');
assertEquals('string', $devRow['storage_type'] ?? '', 'HelpDesk developer_id storage_type');

assertEquals('string', StorageType::fromTypeofdata('V'), 'StorageType V');
assertEquals('datetime', StorageType::fromTypeofdata('DT'), 'StorageType DT');

$wsMap = [];
foreach ((new \App\Db\Query())->select(['uitype', 'fieldtype'])->from('vtiger_ws_fieldtype')->all() as $wsRow) {
	$wsMap[(int) $wsRow['uitype']] = (string) $wsRow['fieldtype'];
}
assertEquals('multiReference', FieldKind::resolve(306, 'account_id', 'V', $wsMap), 'FieldKind MultiReference');

// Runtime still uses legacy resolution until Phase 1b wiring
$helpDesk = Module::getInstance('HelpDesk');
$developerField = Field::getInstance('developer_id', $helpDesk);
assertEquals('owner', $developerField->getUiTypeName(), 'runtime getUiTypeName unchanged');
assertEquals('V', $developerField->getFieldType(), 'runtime getFieldType unchanged');

exit($failures > 0 ? 1 : 0);
