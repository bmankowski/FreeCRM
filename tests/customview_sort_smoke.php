<?php
/**
 * Smoke test for custom view list sort — run via:
 * docker compose exec -T app php tests/customview_sort_smoke.php
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

function assertEq(mixed $actual, mixed $expected, string $msg): void
{
	global $failures;
	if ($actual !== $expected) {
		echo "FAIL: $msg (expected " . var_export($expected, true) . ', got ' . var_export($actual, true) . ")\n";
		++$failures;
	} else {
		echo "OK: $msg\n";
	}
}

$cases = [
	['', '', ''],
	['createdtime,DESC', 'createdtime', 'DESC'],
	['name,asc', 'name', 'ASC'],
	['name', 'name', 'ASC'],
];

foreach ($cases as [$input, $expectedOrderBy, $expectedSortOrder]) {
	$parsed = \App\Modules\CustomView\Models\Record::parseSortValue($input);
	assertEq($parsed['orderBy'], $expectedOrderBy, "parseSortValue orderBy for '$input'");
	assertEq($parsed['sortOrder'], $expectedSortOrder, "parseSortValue sortOrder for '$input'");
}

$formatCases = [
	['', '', ''],
	['createdtime', 'DESC', 'createdtime,DESC'],
	['name', 'ASC', 'name,ASC'],
	['name', '', 'name,ASC'],
];

foreach ($formatCases as [$orderBy, $sortOrder, $expected]) {
	$formatted = \App\Modules\CustomView\Models\Record::formatSortValue($orderBy, $sortOrder);
	assertEq($formatted, $expected, "formatSortValue for orderBy='$orderBy' sortOrder='$sortOrder'");
}

$row = (new \App\Db\Query())
	->select(['cvid', 'sort'])
	->from('vtiger_customview')
	->where(['cvid' => 399, 'entitytype' => 'Candidates'])
	->one();

if ($row) {
	$cv = \App\Modules\CustomView\Models\Record::getInstanceById((int) $row['cvid']);
	assertEq($cv->getSortOrderBy('orderBy'), 'createdtime', 'Candidates All filter orderBy');
	assertEq($cv->getSortOrderBy('sortOrder'), 'DESC', 'Candidates All filter sortOrder');
	assertEq($row['sort'], 'createdtime,DESC', 'Candidates All DB sort column');
} else {
	echo "SKIP: cvid 399 not found (Candidates All filter)\n";
}

$jsonRows = (new \App\Db\Query())
	->from('vtiger_customview')
	->where(['like', 'sort', '{%', false])
	->count();
assertEq($jsonRows, 0, 'no JSON sort values remain in vtiger_customview');

if ($failures > 0) {
	echo "\n$failures failure(s)\n";
	exit(1);
}

echo "\nAll checks passed.\n";
