<?php
/**
 * Smoke test for Settings:LinkAction log list — run via:
 * docker compose exec -T app php tests/link_action_settings_log_smoke.php
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

$parsed = \App\Modules\LinkAction\Services\LinkActionLog::parseQueueTimestamp('2026-06-03T14:22:01+00:00');
assertTrue($parsed === '2026-06-03 14:22:01', 'parseQueueTimestamp ISO8601');
assertTrue(\App\Modules\LinkAction\Services\LinkActionLog::parseQueueTimestamp('') === null, 'parseQueueTimestamp empty');

$moduleClass = \App\Core\Loader::getComponentClassName('Model', 'Module', 'Settings:LinkAction');
assertTrue(class_exists($moduleClass), 'Settings LinkAction Module class resolves');
$module = new $moduleClass();
assertTrue($module->getBaseTable() === 'u_yf_link_action_log', 'base table');
assertTrue($module->hasCreatePermissions() === false, 'read-only module');

$listView = \App\Modules\Settings\Base\Models\ListView::getInstance('Settings:LinkAction');
$listView->set('orderby', 'clicked_at');
$listView->set('sortorder', 'DESC');
$paging = new \App\Modules\Base\Models\Paging();
$paging->set('page', 1);
$entries = $listView->getListViewEntries($paging);
assertTrue(count($entries) >= 1, 'at least one log row in list');

$first = reset($entries);
assertTrue($first instanceof \App\Modules\Settings\LinkAction\Models\Record, 'record model class');
assertTrue($first->getDisplayValue('action') !== '', 'action display value');
assertTrue($first->getDisplayValue('record_id') !== '', 'record display value');

$count = $listView->getListViewCount();
assertTrue($count >= count($entries), 'list count >= page entries');

$blockId = \vtlib\Deprecated::getSettingsBlockId('LBL_LOGS');
$menuRow = (new \App\Db\Query())->from('vtiger_settings_field')
	->where(['blockid' => $blockId, 'name' => 'LBL_LINK_ACTION_LOG'])
	->one();
assertTrue(is_array($menuRow), 'settings menu entry exists');

exit($failures > 0 ? 1 : 0);
