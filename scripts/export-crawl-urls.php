<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Export crawl URLs for all active CRM modules and resolvable standard views.
 *
 * Usage:
 *   php scripts/export-crawl-urls.php
 *
 * Outputs JSON array to stdout:
 *   [{"module":"Contacts","view":"ListView","path":"index.php?module=Contacts&view=ListView"}, ...]
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	echo "scripts/export-crawl-urls.php is a CLI tool only.\n";
	exit(1);
}

chdir(__DIR__ . '/../');
define('REQUEST_MODE', 'Cli');
define('ROOT_DIRECTORY', getcwd() !== false ? getcwd() : __DIR__);

require_once ROOT_DIRECTORY . '/vendor/autoload.php';
require_once ROOT_DIRECTORY . '/vendor/yiisoft/yii2/Yii.php';
require_once ROOT_DIRECTORY . '/config/api.php';
require_once ROOT_DIRECTORY . '/config/config.php';

\App\Core\AppConfig::init($API_CONFIG);
\App\Core\Loader::register();
\App\Debug\Debugger::init();
\App\Cache\Cache::init();

$adminId = \App\Modules\Users\Models\Record::getActiveAdminId();
\App\Modules\Users\Models\Record::setCurrentUserId($adminId);

$standardViews = ['ListView', 'ListPreview', 'DashBoard', 'Edit'];
$notPermittedListModules = ['ModComments', 'Integration', 'DashBoard'];

/**
 * Mirror Loader view resolution without class_exists (some legacy views fatal on autoload).
 */
function viewFileExists(string $moduleName, string $view): bool
{
	$candidates = [$view, ucfirst($view)];
	$moduleDir = str_replace('\\', '/', $moduleName);

	foreach ($candidates as $viewName) {
		$modulePath = ROOT_DIRECTORY . "/src/Modules/{$moduleDir}/Views/{$viewName}.php";
		if (file_exists($modulePath)) {
			return true;
		}

		$basePath = ROOT_DIRECTORY . "/src/Modules/Base/Views/{$viewName}.php";
		if (file_exists($basePath)) {
			return true;
		}

		$legacyPath = ROOT_DIRECTORY . "/modules/{$moduleName}/views/{$viewName}.php";
		if (file_exists($legacyPath)) {
			return true;
		}
	}

	return false;
}

$rows = (new \App\Db\Query())
	->select(['name', 'isentitytype'])
	->from('vtiger_tab')
	->where(['presence' => [0, 2]])
	->orderBy('name')
	->all();

$urls = [];

foreach ($rows as $row) {
	$moduleName = (string) $row['name'];
	$isEntity = (int) $row['isentitytype'] === 1;
	$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);

	if ($moduleModel === false) {
		continue;
	}

	$candidateViews = $isEntity
		? $standardViews
		: array_values(array_unique(array_merge(
			[$moduleModel->getDefaultViewName()],
			$standardViews
		)));

	$seen = [];

	foreach ($candidateViews as $view) {
		if (isset($seen[$view])) {
			continue;
		}

		if ($view === 'ListView' && in_array($moduleName, $notPermittedListModules, true)) {
			continue;
		}

		if (!viewFileExists($moduleName, $view)) {
			continue;
		}

		$seen[$view] = true;
		$urls[] = [
			'module' => $moduleName,
			'view' => $view,
			'path' => 'index.php?module=' . rawurlencode($moduleName) . '&view=' . rawurlencode($view),
		];
	}
}

echo json_encode($urls, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
