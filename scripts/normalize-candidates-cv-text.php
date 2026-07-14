<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * One-time cleanup of stored Candidates.cv_text (PUA bullets, excessive blank lines).
 * Does not re-parse CV documents — applies CvTextNormalizer to existing DB values.
 *
 * Usage:
 *   php scripts/normalize-candidates-cv-text.php [--dry-run] [--execute] [--batch-size=500]
 */

declare(strict_types=1);

use App\Modules\Candidates\Services\CvTextNormalizer;

if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	echo "scripts/normalize-candidates-cv-text.php is a CLI tool only.\n";
	exit(1);
}

chdir(__DIR__ . '/../');

require_once getcwd() . '/vendor/autoload.php';
\App\Modules\Cron\Bootstrap::init();

$options = parseOptions($argv);
$batchSize = max(1, $options['batch_size']);
$execute = $options['execute'];
$dryRun = !$execute;

$total = countCandidatesWithCvText();
echo sprintf("Candidates with cv_text: %d\n", $total);
echo $dryRun ? "Mode: dry-run (pass --execute to write)\n" : "Mode: execute\n";

$lastId = 0;
$processed = 0;
$updated = 0;
$unchanged = 0;

while (true) {
	$rows = fetchBatch($lastId, $batchSize);
	if ($rows === []) {
		break;
	}

	foreach ($rows as $row) {
		$candidateId = (int) $row['candidatesid'];
		$lastId = $candidateId;
		++$processed;

		$original = (string) $row['cv_text'];
		$normalized = CvTextNormalizer::normalizeStoredText($original);
		if ($normalized === $original) {
			++$unchanged;
			continue;
		}

		++$updated;
		if ($execute) {
			\App\Db\Db::getInstance()->createCommand()->update(
				'u_yf_candidatescf',
				['cv_text' => $normalized],
				['candidatesid' => $candidateId]
			)->execute();
		}
	}

	echo sprintf(
		"Progress: processed=%d updated=%d unchanged=%d lastId=%d\n",
		$processed,
		$updated,
		$unchanged,
		$lastId
	);
}

echo sprintf(
	"Done. processed=%d would_update=%d unchanged=%d%s\n",
	$processed,
	$updated,
	$unchanged,
	$dryRun ? ' (dry-run, no writes)' : ''
);

/**
 * @return array{batch_size: int, execute: bool}
 */
function parseOptions(array $argv): array
{
	$options = [
		'batch_size' => 500,
		'execute' => false,
	];

	foreach (array_slice($argv, 1) as $arg) {
		if ($arg === '--execute') {
			$options['execute'] = true;
		} elseif ($arg === '--dry-run') {
			$options['execute'] = false;
		} elseif (str_starts_with($arg, '--batch-size=')) {
			$options['batch_size'] = max(1, (int) substr($arg, 13));
		} elseif ($arg === '--help' || $arg === '-h') {
			echo "Usage: php scripts/normalize-candidates-cv-text.php [--dry-run] [--execute] [--batch-size=500]\n";
			exit(0);
		} else {
			fwrite(STDERR, "Unknown option: {$arg}\n");
			exit(1);
		}
	}

	return $options;
}

function countCandidatesWithCvText(): int
{
	return (int) (new \App\Db\Query())
		->from(['cf' => 'u_yf_candidatescf'])
		->innerJoin(['e' => 'vtiger_crmentity'], 'e.crmid = cf.candidatesid')
		->where(['e.deleted' => 0])
		->andWhere(['not', ['cf.cv_text' => null]])
		->andWhere(['<>', 'cf.cv_text', ''])
		->count('*', \App\Db\Db::getInstance());
}

/**
 * @return list<array{candidatesid: int|string, cv_text: string|null}>
 */
function fetchBatch(int $afterId, int $limit): array
{
	return (new \App\Db\Query())
		->select(['cf.candidatesid', 'cf.cv_text'])
		->from(['cf' => 'u_yf_candidatescf'])
		->innerJoin(['e' => 'vtiger_crmentity'], 'e.crmid = cf.candidatesid')
		->where(['e.deleted' => 0])
		->andWhere(['>', 'cf.candidatesid', $afterId])
		->andWhere(['not', ['cf.cv_text' => null]])
		->andWhere(['<>', 'cf.cv_text', ''])
		->orderBy(['cf.candidatesid' => SORT_ASC])
		->limit($limit)
		->all(\App\Db\Db::getInstance());
}
