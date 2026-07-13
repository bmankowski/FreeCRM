<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Soft-delete Candidates (and optionally RecruitmentApplication records) created
 * on a given calendar day via Record::delete(), then permanently purge the recycle bin.
 *
 * Usage:
 *   php scripts/cleanup-candidates-created-on-date.php [--date=YYYY-MM-DD] [--dry-run]
 *       [--execute] [--with-applications] [--skip-empty-recycle-bin]
 *
 * Examples:
 *   php scripts/cleanup-candidates-created-on-date.php --dry-run
 *   php scripts/cleanup-candidates-created-on-date.php --date=2026-07-13 --dry-run
 *   php scripts/cleanup-candidates-created-on-date.php --date=2026-07-13 --execute
 *   php scripts/cleanup-candidates-created-on-date.php --date=2026-07-13 --execute --with-applications
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	echo "scripts/cleanup-candidates-created-on-date.php is a CLI tool only.\n";
	exit(1);
}

chdir(__DIR__ . '/../');

require_once getcwd() . '/vendor/autoload.php';
\App\Modules\Cron\Bootstrap::init();

\App\Http\Vtiger_Session::init();

$_SERVER['HTTP_USER_AGENT'] ??= 'cleanup-candidates-created-on-date-cli';
$_SERVER['REMOTE_ADDR'] ??= '127.0.0.1';
$_SERVER['REQUEST_URI'] ??= '/cli/cleanup-candidates-created-on-date';

$options = parseOptions($argv);

$adminId = \App\Modules\Users\Models\Record::getActiveAdminId();
if ($adminId <= 0) {
	fwrite(STDERR, "No active admin user found.\n");
	exit(1);
}
\App\Modules\Users\Models\Record::setCurrentUserId($adminId);

$candidateIds = fetchCandidateIds($options['date']);
$applicationIds = $options['with_applications']
	? fetchRecruitmentApplicationIds($options['date'])
	: [];

$recycleBinCount = countRecycleBinRecords();

echo sprintf(
	"Date=%s dry-run=%s with-applications=%s empty-recycle-bin=%s candidates=%d applications=%d recycle-bin=%d\n",
	$options['date'],
	$options['dry_run'] ? 'yes' : 'no',
	$options['with_applications'] ? 'yes' : 'no',
	$options['empty_recycle_bin'] ? 'yes' : 'no',
	count($candidateIds),
	count($applicationIds),
	$recycleBinCount
);

if ($candidateIds === [] && $applicationIds === []) {
	if (!$options['dry_run'] && $options['empty_recycle_bin'] && $recycleBinCount > 0) {
		$purged = emptyRecycleBin();
		echo sprintf("Recycle bin purged: %d record(s)\n", $purged);
		exit(0);
	}
	echo "Nothing to do.\n";
	exit(0);
}

printPreview($candidateIds, $applicationIds);

if ($options['dry_run']) {
	echo "Dry run complete — no records deleted. Pass --execute to delete.\n";
	exit(0);
}

$stats = [
	'applications' => ['deleted' => 0, 'failed' => 0],
	'candidates' => ['deleted' => 0, 'failed' => 0],
];

foreach ($applicationIds as $recordId) {
	if (deleteRecord('RecruitmentApplication', $recordId)) {
		++$stats['applications']['deleted'];
	} else {
		++$stats['applications']['failed'];
	}
}

foreach ($candidateIds as $recordId) {
	if (deleteRecord('Candidates', $recordId)) {
		++$stats['candidates']['deleted'];
	} else {
		++$stats['candidates']['failed'];
	}
}

echo sprintf(
	"Done. applications deleted=%d failed=%d; candidates deleted=%d failed=%d\n",
	$stats['applications']['deleted'],
	$stats['applications']['failed'],
	$stats['candidates']['deleted'],
	$stats['candidates']['failed']
);

$exitCode = ($stats['applications']['failed'] + $stats['candidates']['failed']) > 0 ? 1 : 0;

if ($options['empty_recycle_bin']) {
	$purged = emptyRecycleBin();
	echo sprintf("Recycle bin purged: %d record(s)\n", $purged);
}

exit($exitCode);

/**
 * @param list<string> $argv
 * @return array{date: string, dry_run: bool, with_applications: bool, empty_recycle_bin: bool}
 */
function parseOptions(array $argv): array
{
	$options = [
		'date' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
		'dry_run' => true,
		'with_applications' => false,
		'empty_recycle_bin' => true,
	];

	foreach (array_slice($argv, 1) as $arg) {
		if ($arg === '--dry-run') {
			$options['dry_run'] = true;
			continue;
		}
		if ($arg === '--execute') {
			$options['dry_run'] = false;
			continue;
		}
		if ($arg === '--with-applications') {
			$options['with_applications'] = true;
			continue;
		}
		if ($arg === '--skip-empty-recycle-bin') {
			$options['empty_recycle_bin'] = false;
			continue;
		}
		if (str_starts_with($arg, '--date=')) {
			$date = substr($arg, strlen('--date='));
			if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
				fwrite(STDERR, "Invalid --date value: {$date}\n");
				exit(1);
			}
			$options['date'] = $date;
			continue;
		}

		fwrite(STDERR, "Unknown option: {$arg}\n");
		exit(1);
	}

	return $options;
}

/**
 * @return list<int>
 */
function fetchCandidateIds(string $date): array
{
	$db = \App\Database\PearDatabase::getInstance();
	$result = $db->pquery(
		'SELECT c.candidatesid
		 FROM u_yf_candidates c
		 INNER JOIN vtiger_crmentity e ON e.crmid = c.candidatesid
		 WHERE e.deleted = 0 AND DATE(e.createdtime) = ?
		 ORDER BY c.candidatesid',
		[$date]
	);

	$ids = [];
	while ($row = $db->fetchByAssoc($result)) {
		$ids[] = (int) $row['candidatesid'];
	}

	return $ids;
}

/**
 * @return list<int>
 */
function fetchRecruitmentApplicationIds(string $date): array
{
	$db = \App\Database\PearDatabase::getInstance();
	$result = $db->pquery(
		'SELECT ra.recruitmentapplicationid
		 FROM vtiger_recruitmentapplication ra
		 INNER JOIN vtiger_crmentity e ON e.crmid = ra.recruitmentapplicationid
		 WHERE e.deleted = 0 AND DATE(e.createdtime) = ?
		 ORDER BY ra.recruitmentapplicationid',
		[$date]
	);

	$ids = [];
	while ($row = $db->fetchByAssoc($result)) {
		$ids[] = (int) $row['recruitmentapplicationid'];
	}

	return $ids;
}

/**
 * @param list<int> $candidateIds
 * @param list<int> $applicationIds
 */
function printPreview(array $candidateIds, array $applicationIds): void
{
	if ($applicationIds !== []) {
		$sample = array_slice($applicationIds, 0, 10);
		echo 'RecruitmentApplication sample IDs: ' . implode(', ', $sample);
		if (count($applicationIds) > 10) {
			echo ' … (+' . (count($applicationIds) - 10) . ' more)';
		}
		echo "\n";
	}

	if ($candidateIds === []) {
		return;
	}

	$db = \App\Database\PearDatabase::getInstance();
	$placeholders = implode(',', array_fill(0, min(10, count($candidateIds)), '?'));
	$result = $db->pquery(
		"SELECT c.candidatesid, c.name, c.application_id, e.createdtime
		 FROM u_yf_candidates c
		 INNER JOIN vtiger_crmentity e ON e.crmid = c.candidatesid
		 WHERE c.candidatesid IN ({$placeholders})
		 ORDER BY c.candidatesid",
		array_slice($candidateIds, 0, 10)
	);

	echo "Candidates sample:\n";
	while ($row = $db->fetchByAssoc($result)) {
		echo sprintf(
			"  %d  %s  application_id=%s  created=%s\n",
			(int) $row['candidatesid'],
			(string) $row['name'],
			(string) $row['application_id'],
			(string) $row['createdtime']
		);
	}

	if (count($candidateIds) > 10) {
		echo sprintf("  … (+%d more)\n", count($candidateIds) - 10);
	}
}

function countRecycleBinRecords(): int
{
	return (int) (new \App\Db\Query())
		->from('vtiger_crmentity')
		->where(['deleted' => 1])
		->count();
}

function emptyRecycleBin(): int
{
	$before = countRecycleBinRecords();
	if ($before === 0) {
		return 0;
	}

	$module = \App\Modules\RecycleBin\Models\Module::getInstance('RecycleBin');
	if (!$module->emptyRecycleBin()) {
		throw new \RuntimeException('emptyRecycleBin() failed');
	}

	return $before;
}

function deleteRecord(string $moduleName, int $recordId): bool
{
	try {
		$record = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
		if (!$record || !$record->getId()) {
			fwrite(STDERR, "Skip {$moduleName} {$recordId}: record not found\n");
			return false;
		}

		$record->delete();
		echo "Deleted {$moduleName} {$recordId}\n";
		return true;
	} catch (\Throwable $e) {
		fwrite(STDERR, "Failed {$moduleName} {$recordId}: {$e->getMessage()}\n");
		return false;
	}
}
