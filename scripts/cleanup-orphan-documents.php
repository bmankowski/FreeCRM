<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Soft-delete (or permanently purge) Documents that have no active parent link
 * and are not used as email-template attachments.
 *
 * Usage:
 *   php scripts/cleanup-orphan-documents.php [--dry-run] [--missing-file-only]
 *       [--batch-size=500] [--limit=0] [--permanent] [--verify-disk]
 *
 * Examples:
 *   php scripts/cleanup-orphan-documents.php --dry-run
 *   php scripts/cleanup-orphan-documents.php --dry-run --missing-file-only
 *   php scripts/cleanup-orphan-documents.php --missing-file-only --batch-size=500 --limit=100
 *   php scripts/cleanup-orphan-documents.php --missing-file-only --permanent
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	echo "scripts/cleanup-orphan-documents.php is a CLI tool only.\n";
	exit(1);
}

chdir(__DIR__ . '/../');

require_once getcwd() . '/vendor/autoload.php';
\App\Modules\Cron\Bootstrap::init();

\App\Http\Vtiger_Session::init();

$options = parseOptions($argv);
$adminId = \App\Modules\Users\Models\Record::getActiveAdminId();
if ($adminId <= 0) {
	fwrite(STDERR, "No active admin user found.\n");
	exit(1);
}
\App\Modules\Users\Models\Record::setCurrentUserId($adminId);

$totalCandidates = countOrphanCandidates($options);
echo sprintf(
	"Orphan document candidates: %d (dry-run=%s, missing-file-only=%s, verify-disk=%s, permanent=%s, batch-size=%d, limit=%s)\n",
	$totalCandidates,
	$options['dry_run'] ? 'yes' : 'no',
	$options['missing_file_only'] ? 'yes' : 'no',
	$options['verify_disk'] ? 'yes' : 'no',
	$options['permanent'] ? 'yes' : 'no',
	$options['batch_size'],
	$options['limit'] > 0 ? (string) $options['limit'] : 'none'
);

if ($totalCandidates === 0) {
	echo "Nothing to do.\n";
	exit(0);
}

if ($options['dry_run']) {
	$sample = fetchOrphanBatch($options, 0, min(10, $totalCandidates));
	echo "Sample IDs: " . implode(', ', $sample) . "\n";
	echo "Dry run complete — no records deleted.\n";
	exit(0);
}

$recycleBinModule = $options['permanent']
	? \App\Modules\RecycleBin\Models\Module::getInstance('RecycleBin')
	: null;

$processed = 0;
$deleted = 0;
$skipped = 0;
$failed = 0;
$lastId = 0;

while (true) {
	if ($options['limit'] > 0 && $processed >= $options['limit']) {
		break;
	}

	$remaining = $options['limit'] > 0 ? $options['limit'] - $processed : $options['batch_size'];
	$batchLimit = min($options['batch_size'], $remaining);
	$batch = fetchOrphanBatch($options, $lastId, $batchLimit);
	if ($batch === []) {
		break;
	}

	foreach ($batch as $documentId) {
		$lastId = $documentId;
		++$processed;

		try {
			if ($options['verify_disk'] && !isMissingOnDisk($documentId)) {
				++$skipped;
				continue;
			}

			$recordModel = \App\Modules\Base\Models\Record::getInstanceById($documentId, 'Documents');
			if (!$recordModel->isDeletable()) {
				++$skipped;
				fwrite(STDERR, sprintf("Skip (not deletable): %d\n", $documentId));
				continue;
			}

			$recordModel->delete();
			if ($recycleBinModule !== null) {
				$recycleBinModule->deleteRecords([$documentId]);
			}
			++$deleted;
		} catch (\Throwable $e) {
			++$failed;
			fwrite(STDERR, sprintf("Failed %d: %s\n", $documentId, $e->getMessage()));
		}
	}

	echo sprintf(
		"Progress: processed=%d deleted=%d skipped=%d failed=%d lastId=%d\n",
		$processed,
		$deleted,
		$skipped,
		$failed,
		$lastId
	);
}

echo sprintf(
	"Done. processed=%d deleted=%d skipped=%d failed=%d\n",
	$processed,
	$deleted,
	$skipped,
	$failed
);

exit($failed > 0 ? 1 : 0);

/**
 * @param list<string> $argv
 * @return array{
 *     dry_run: bool,
 *     missing_file_only: bool,
 *     verify_disk: bool,
 *     permanent: bool,
 *     batch_size: int,
 *     limit: int
 * }
 */
function parseOptions(array $argv): array
{
	$options = [
		'dry_run' => false,
		'missing_file_only' => false,
		'verify_disk' => false,
		'permanent' => false,
		'batch_size' => 500,
		'limit' => 0,
	];

	foreach (array_slice($argv, 1) as $arg) {
		if ($arg === '--dry-run') {
			$options['dry_run'] = true;
			continue;
		}
		if ($arg === '--missing-file-only') {
			$options['missing_file_only'] = true;
			continue;
		}
		if ($arg === '--verify-disk') {
			$options['verify_disk'] = true;
			continue;
		}
		if ($arg === '--permanent') {
			$options['permanent'] = true;
			continue;
		}
		if (str_starts_with($arg, '--batch-size=')) {
			$options['batch_size'] = max(1, (int) substr($arg, 13));
			continue;
		}
		if (str_starts_with($arg, '--limit=')) {
			$options['limit'] = max(0, (int) substr($arg, 8));
			continue;
		}
		if ($arg === '--help' || $arg === '-h') {
			echo <<<'HELP'
Usage:
  php scripts/cleanup-orphan-documents.php [options]

Options:
  --dry-run              Count and show sample IDs only; no deletes
  --missing-file-only    Restrict to legacy storage/oss_mailscanner/ attachments
  --verify-disk          Skip records whose attachment file exists on disk
  --permanent            Purge immediately after soft-delete (skip Recycle Bin)
  --batch-size=N         Records per batch (default 500)
  --limit=N              Stop after N processed records (0 = no limit)
  --help                 Show this help

HELP;
			exit(0);
		}

		fwrite(STDERR, "Unknown option: {$arg}\n");
		exit(1);
	}

	if ($options['verify_disk'] && !$options['missing_file_only']) {
		fwrite(STDERR, "--verify-disk requires --missing-file-only (otherwise use full orphan cleanup).\n");
		exit(1);
	}

	return $options;
}

/**
 * @param array{
 *     dry_run: bool,
 *     missing_file_only: bool,
 *     verify_disk: bool,
 *     permanent: bool,
 *     batch_size: int,
 *     limit: int
 * } $options
 */
function orphanQuery(array $options): \App\Db\Query
{
	$activeLinkSubQuery = (new \App\Db\Query())
		->select(['sr.notesid'])
		->from(['sr' => 'vtiger_senotesrel'])
		->innerJoin(['p' => 'vtiger_crmentity'], 'p.crmid = sr.crmid')
		->where(['p.deleted' => 0]);

	$templateLinkSubQuery = (new \App\Db\Query())
		->select(['crmid'])
		->from('u_yf_documents_emailtemplates');

	$query = (new \App\Db\Query())
		->select(['n.notesid'])
		->from(['n' => 'vtiger_notes'])
		->innerJoin(['ce' => 'vtiger_crmentity'], 'ce.crmid = n.notesid')
		->where(['ce.deleted' => 0])
		->andWhere(['not in', 'n.notesid', $activeLinkSubQuery])
		->andWhere(['not in', 'n.notesid', $templateLinkSubQuery])
		->orderBy(['n.notesid' => SORT_ASC]);

	if ($options['missing_file_only']) {
		$query
			->innerJoin(['sar' => 'vtiger_seattachmentsrel'], 'sar.crmid = n.notesid')
			->innerJoin(['a' => 'vtiger_attachments'], 'a.attachmentsid = sar.attachmentsid')
			->andWhere(['like', 'a.path', 'storage/oss_mailscanner/', false]);
	}

	return $query;
}

/**
 * @param array{
 *     dry_run: bool,
 *     missing_file_only: bool,
 *     verify_disk: bool,
 *     permanent: bool,
 *     batch_size: int,
 *     limit: int
 * } $options
 */
function countOrphanCandidates(array $options): int
{
	return (int) orphanQuery($options)->count('*', \App\Db\Db::getInstance());
}

/**
 * @param array{
 *     dry_run: bool,
 *     missing_file_only: bool,
 *     verify_disk: bool,
 *     permanent: bool,
 *     batch_size: int,
 *     limit: int
 * } $options
 * @return list<int>
 */
function fetchOrphanBatch(array $options, int $afterId, int $batchSize): array
{
	$query = orphanQuery($options)
		->andWhere(['>', 'n.notesid', $afterId])
		->limit($batchSize);

	$ids = $query->column();
	if ($ids === []) {
		return [];
	}

	return array_map('intval', $ids);
}

function isMissingOnDisk(int $documentId): bool
{
	$row = (new \App\Db\Query())
		->select([
			'vtiger_notes.filename',
			'vtiger_notes.filelocationtype',
			'vtiger_notes.filestatus',
			'vtiger_attachments.attachmentsid',
			'vtiger_attachments.path',
			'vtiger_attachments.name',
		])
		->from('vtiger_notes')
		->innerJoin('vtiger_seattachmentsrel', 'vtiger_seattachmentsrel.crmid = vtiger_notes.notesid')
		->innerJoin('vtiger_attachments', 'vtiger_attachments.attachmentsid = vtiger_seattachmentsrel.attachmentsid')
		->where(['vtiger_notes.notesid' => $documentId])
		->one();

	if (!$row) {
		return true;
	}
	if ((string) ($row['filelocationtype'] ?? '') !== 'I' || (int) ($row['filestatus'] ?? 0) !== 1) {
		return true;
	}

	$fileName = (string) ($row['filename'] ?? '');
	if ($fileName === '') {
		return true;
	}

	$storedName = \App\Utils\ListViewUtils::decodeHtml((string) ($row['name'] ?? $fileName));
	$filePath = realpath(
		(\defined('ROOT_DIRECTORY') ? ROOT_DIRECTORY : getcwd()) . DIRECTORY_SEPARATOR . ($row['path'] ?? '')
		. ($row['attachmentsid'] ?? '') . '_' . $storedName
	);

	return $filePath === false || !is_file($filePath);
}
