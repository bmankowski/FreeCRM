<?php
/**
 * Smoke test for the crmentity-first ListView pagination fast path.
 *
 * Verifies that QueryGenerator::buildEntityDateOrderingIdQuery():
 *   - fires for a plain entity list sorted by a vtiger_crmentity datetime column,
 *   - bails for any module-column filter or non-crmentity sort,
 *   - returns the same page of records as the legacy full-join query.
 *
 * Run via: docker compose exec -T app php tests/listview_entity_date_fastpath_smoke.php
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

$module = 'Candidates';
$failures = 0;

function assertTrue(bool $cond, string $msg): void
{
	global $failures;
	if ($cond) {
		echo "OK: $msg\n";
	} else {
		echo "FAIL: $msg\n";
		++$failures;
	}
}

function newGenerator(string $module): \App\QueryField\QueryGenerator
{
	$qg = new \App\QueryField\QueryGenerator($module);
	$qg->loadListFields();
	return $qg;
}

// 1. Eligible: plain list ordered by createdtime
$qg = newGenerator($module);
$qg->setOrder('createdtime', 'DESC');
$idQuery = $qg->buildEntityDateOrderingIdQuery();
assertTrue($idQuery !== null, 'fast path fires for createdtime DESC');

// 2. Eligible: ordered by modifiedtime
$qg = newGenerator($module);
$qg->setOrder('modifiedtime', 'ASC');
assertTrue($qg->buildEntityDateOrderingIdQuery() !== null, 'fast path fires for modifiedtime ASC');

// 3. Bails: ordered by a module-table column
$qg = newGenerator($module);
$qg->setOrder('name', 'ASC');
assertTrue($qg->buildEntityDateOrderingIdQuery() === null, 'fast path bails for module-column sort (name)');

// 4. Bails: any module-column filter present
$qg = newGenerator($module);
$qg->setOrder('createdtime', 'DESC');
$qg->addCondition('candidate_status', 'Pracownik', 'e');
assertTrue($qg->buildEntityDateOrderingIdQuery() === null, 'fast path bails when a module-column filter is set');

// 5. Bails: no order at all
$qg = newGenerator($module);
assertTrue($qg->buildEntityDateOrderingIdQuery() === null, 'fast path bails without ORDER BY');

// 6. Correctness + timing: fast-path ids vs legacy full-join page (same membership, ordered)
$pageLimit = 20;

$qg = newGenerator($module);
$qg->setOrder('createdtime', 'DESC');
$idQuery = $qg->buildEntityDateOrderingIdQuery();

$t0 = microtime(true);
$fastIds = array_map('intval', $idQuery->limit($pageLimit)->offset(0)->column());
$fastMs = round((microtime(true) - $t0) * 1000, 1);

$t0 = microtime(true);
$legacyRows = (clone $qg->createQuery())->limit($pageLimit)->offset(0)->all();
$legacyMs = round((microtime(true) - $t0) * 1000, 1);
$legacyIds = array_map(static fn($r) => (int) $r['id'], $legacyRows);

echo "NOTE: fast id-query {$fastMs}ms vs legacy full-join {$legacyMs}ms (page of {$pageLimit})\n";

assertTrue(count($fastIds) === $pageLimit, "fast path returns a full page ($pageLimit ids)");

$fastSorted = $fastIds;
$legacySorted = $legacyIds;
sort($fastSorted);
sort($legacySorted);
assertTrue($fastSorted === $legacySorted, 'fast-path page has same membership as legacy page');

// Ordering must be newest-first by createdtime
if (!empty($fastIds)) {
	$timeByCrmid = (new \App\Db\Query())
		->select(['createdtime', 'crmid'])
		->from('vtiger_crmentity')
		->where(['crmid' => $fastIds])
		->indexBy('crmid')
		->column();
	$ordered = [];
	foreach ($fastIds as $cid) {
		$ordered[] = $timeByCrmid[$cid] ?? '';
	}
	$monotonic = true;
	for ($i = 1, $n = count($ordered); $i < $n; $i++) {
		if ($ordered[$i] > $ordered[$i - 1]) {
			$monotonic = false;
			break;
		}
	}
	assertTrue($monotonic, 'fast-path ids are ordered by createdtime DESC');
}

// 7. End-to-end through the ListView model (uses the fast path internally)
$lv = \App\Modules\Base\Models\ListView::getInstance($module, 0);
$lv->set('orderby', 'createdtime');
$lv->set('sortorder', 'DESC');
$paging = new \App\Modules\Base\Models\Paging();
$paging->set('page', 1);
$entries = $lv->getListViewEntries($paging);
assertTrue(count($entries) > 0 && count($entries) <= $pageLimit, 'ListView::getListViewEntries returns a bounded page via fast path');

if ($failures > 0) {
	echo "\n$failures failure(s)\n";
	exit(1);
}

echo "\nAll checks passed.\n";
