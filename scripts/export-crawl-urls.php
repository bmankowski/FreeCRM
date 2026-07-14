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
 *
 * Entity modules also export every DetailView tab (summary, details, comments, related lists)
 * for one accessible sample record per module.
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
$skippedModules = ['Import'];
$notPermittedListModules = ['ModComments', 'Integration', 'DashBoard'];

/**
 * Mirror Loader view resolution without class_exists (some legacy views fatal on autoload).
 */
function viewFileExists(string $moduleName, string $view, bool $moduleOnly = false): bool
{
	$candidates = [$view, ucfirst($view)];
	$moduleDir = str_replace('\\', '/', $moduleName);

	foreach ($candidates as $viewName) {
		$modulePath = ROOT_DIRECTORY . "/src/Modules/{$moduleDir}/Views/{$viewName}.php";
		if (file_exists($modulePath)) {
			return true;
		}

		if ($moduleOnly) {
			continue;
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

function findSampleRecordId(string $moduleName): ?int
{
	if ($moduleName === 'TemplateElements') {
		$id = (new \App\Db\Query())
			->select(['te.templateelementsid'])
			->from(['te' => 'u_yf_templateelements'])
			->innerJoin(['ce' => 'vtiger_crmentity'], 'ce.crmid = te.templateelementsid')
			->where([
				'ce.setype' => $moduleName,
				'ce.deleted' => 0,
				'te.status' => 1,
			])
			->orderBy(['te.templateelementsid' => SORT_DESC])
			->scalar();

		return ($id !== false && $id !== null) ? (int) $id : null;
	}

	$id = (new \App\Db\Query())
		->select(['crmid'])
		->from('vtiger_crmentity')
		->where([
			'setype' => $moduleName,
			'deleted' => 0,
		])
		->orderBy(['crmid' => SORT_DESC])
		->scalar();

	return ($id !== false && $id !== null) ? (int) $id : null;
}

function normalizeCrawlPath(string $url, string $tabLabel): string
{
	if ($url === '' || str_starts_with($url, 'javascript:')) {
		return '';
	}

	$url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	if (!str_starts_with($url, 'index.php')) {
		$url = 'index.php?' . ltrim($url, '?&');
	}
	if ($tabLabel !== '' && !preg_match('/(?:^|[?&])tab_label=/', $url)) {
		$separator = str_contains($url, '?') ? '&' : '?';
		$url .= $separator . 'tab_label=' . rawurlencode($tabLabel);
	}

	return $url;
}

function detailTabViewLabel(\App\Modules\Base\Models\Link $link): string
{
	if ($link->getType() === 'DETAILVIEWRELATED') {
		$relatedModuleName = $link->get('relatedModuleName');
		if (!empty($relatedModuleName)) {
			return 'Detail:' . $relatedModuleName;
		}
	}

	return 'Detail:' . $link->getLabel();
}

function appendDetailTabUrls(
	array &$urls,
	string $moduleName,
	\App\Modules\Base\Models\Module $moduleModel,
): void {
	$detailViewName = $moduleModel->getDetailViewName();
	if (!viewFileExists($moduleName, $detailViewName) || !$moduleModel->isPermitted('DetailView')) {
		return;
	}

	$recordId = findSampleRecordId($moduleName);
	if ($recordId === null || !\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'DetailView', $recordId)) {
		return;
	}

	try {
		$detailView = \App\Modules\Base\Models\DetailView::getInstance($moduleName, $recordId);
		if (!$detailView->getRecord()->isViewable()) {
			return;
		}
		$detailViewLinks = $detailView->getDetailViewLinks([
			'MODULE' => $moduleName,
			'RECORD' => $recordId,
			'VIEW' => $detailViewName,
		]);
	} catch (\Throwable) {
		return;
	}

	$tabSeen = [];
	foreach (['DETAILVIEWTAB', 'DETAILVIEWRELATED'] as $tabType) {
		foreach ($detailViewLinks[$tabType] ?? [] as $link) {
			$tabLabel = $link->getLabel();
			$dedupeKey = $tabType . ':' . $tabLabel;
			if (isset($tabSeen[$dedupeKey])) {
				continue;
			}

			$path = normalizeCrawlPath($link->getUrl(), $tabLabel);
			if ($path === '') {
				continue;
			}

			$tabSeen[$dedupeKey] = true;
			$urls[] = [
				'module' => $moduleName,
				'view' => detailTabViewLabel($link),
				'path' => $path,
			];
		}
	}
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
	if (in_array($moduleName, $skippedModules, true)) {
		continue;
	}
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

		if ($view === 'ListPreview' && !$moduleModel->isListPreviewSupported()) {
			continue;
		}

		if ($view === 'Edit' && (!$moduleModel->isPermitted('CreateView') || !$moduleModel->isPermitted('EditView'))) {
			continue;
		}

		if (!viewFileExists(
			$moduleName,
			$view,
			!$isEntity && in_array($view, $standardViews, true)
		)) {
			continue;
		}

		$seen[$view] = true;
		$urls[] = [
			'module' => $moduleName,
			'view' => $view,
			'path' => 'index.php?module=' . rawurlencode($moduleName) . '&view=' . rawurlencode($view),
		];
	}

	if ($isEntity) {
		appendDetailTabUrls($urls, $moduleName, $moduleModel);
	}
}

echo json_encode($urls, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
