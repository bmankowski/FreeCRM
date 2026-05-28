<?php
/**
 * Idempotent backfill: Kandydaci with application_id -> RecruitmentApplication.
 *
 * Usage:
 *   docker compose exec -T app php src/Modules/RecruitmentApplication/scripts/backfillFromKandydaci.php
 *   docker compose exec -T app php src/Modules/RecruitmentApplication/scripts/backfillFromKandydaci.php --dry-run
 *   docker compose exec -T app php src/Modules/RecruitmentApplication/scripts/backfillFromKandydaci.php --limit=100
 */

declare(strict_types=1);

$dryRun = in_array('--dry-run', $argv ?? [], true);
$limit = null;
foreach ($argv ?? [] as $arg) {
	if (str_starts_with($arg, '--limit=')) {
		$limit = (int) substr($arg, 8);
	}
}

$rootDirectory = dirname(__DIR__, 4);
chdir($rootDirectory);
require_once $rootDirectory . '/vendor/autoload.php';
\App\Modules\Cron\Bootstrap::init();
\App\Modules\Users\Models\Record::setCurrentUserId(
	\App\Modules\Users\Models\Record::getUserIdByName('automat')
);

$dupes = (new \App\Db\Query())
	->select(['application_id', 'cnt' => 'COUNT(*)'])
	->from(['k' => 'u_yf_kandydaci'])
	->innerJoin(['e' => 'vtiger_crmentity'], 'e.crmid = k.kandydaciid')
	->where(['e.deleted' => 0])
	->andWhere(['not', ['k.application_id' => null]])
	->andWhere(['!=', 'k.application_id', ''])
	->groupBy('k.application_id')
	->having(['>', 'cnt', 1])
	->all();
if ($dupes !== []) {
	fwrite(STDERR, "Abort: duplicate application_id values on Kandydaci.\n");
	exit(1);
}

$query = (new \App\Db\Query())
	->select(['k.kandydaciid', 'k.application_id', 'k.name', 'k.telefon', 'k.application_json_content'])
	->from(['k' => 'u_yf_kandydaci'])
	->innerJoin(['e' => 'vtiger_crmentity'], 'e.crmid = k.kandydaciid')
	->where(['e.deleted' => 0])
	->andWhere(['not', ['k.application_id' => null]])
	->andWhere(['!=', 'k.application_id', '']);
if ($limit !== null) {
	$query->limit($limit);
}
$rows = $query->all();

$importer = new \App\Modules\RecruitmentApplication\Services\RecruitmentApplicationImporter();
$created = 0;
$skipped = 0;
$errors = 0;

foreach ($rows as $row) {
	$applicationNumber = (string) $row['application_id'];
	try {
		if ($importer->isApplicationImported($applicationNumber)) {
			++$skipped;
			continue;
		}
		if ($dryRun) {
			++$created;
			continue;
		}
		$dto = buildDtoFromKandydaciRow($row);
		$record = $importer->createFromDto($dto);
		$record->set('cf_303289', (int) $row['kandydaciid']);
		$record->save();
		++$created;
	} catch (\Throwable $e) {
		++$errors;
		\App\Log\Log::error($e);
		echo "Error for application_id {$applicationNumber}: " . $e->getMessage() . "\n";
	}
}

echo sprintf(
	"Backfill complete. created=%d skipped=%d errors=%d dry_run=%s\n",
	$created,
	$skipped,
	$errors,
	$dryRun ? 'yes' : 'no'
);

function buildDtoFromKandydaciRow(array $row): \App\Modules\RecruitmentApplication\Services\CvImport\CvApplicationDto
{
	$dto = new \App\Modules\RecruitmentApplication\Services\CvImport\CvApplicationDto();
	$dto->applicationNumber = (string) $row['application_id'];
	$dto->rawJsonData = (string) ($row['application_json_content'] ?? '');
	$dto->jsonFilePath = '';
	$dto->pendingDirectory = '';
	$dto->candidateName = (string) ($row['name'] ?? '');
	$dto->candidateEmail = '';
	$dto->candidateOriginalPhone = (string) ($row['telefon'] ?? '');
	$dto->candidateTransformedPhone = (string) ($row['telefon'] ?? '');
	$dto->cvAttachmentPath = '';
	$dto->projectId = '';
	$dto->sourceId = '';
	$dto->agreeToContact = '';
	$dto->originalFilename = '';
	$dto->availability = '';
	$dto->financialExpectations = '';
	$dto->message = '';
	$dto->preferredContractType = '';
	$dto->expectedSalary = '';
	$dto->jobTitle = '';
	$dto->isReferredByEmployee = false;
	$dto->referredByEmployee = '';
	$dto->referredOnPosition = '';
	$dto->referredByEmail = '';
	$dto->formType = '';
	$dto->submittedAt = '';
	$dto->postId = '';
	$dto->formId = '';
	$dto->referrerUrl = '';
	$dto->formLanguage = '';
	$dto->cvOriginalFilename = '';
	$dto->cvSavedFilename = '';
	$dto->cvAttachmentUrl = '';
	$dto->isMetForm = false;

	if ($dto->rawJsonData !== '') {
		$data = json_decode($dto->rawJsonData, true);
		if (is_array($data)) {
			if (isset($data['entries'])) {
				try {
					$parsed = \App\Modules\RecruitmentApplication\Services\CvImport\CvJsonParser::parseFile(
						'/tmp/',
						writeTempJson($dto->rawJsonData),
						$dto->applicationNumber
					);
					return $parsed;
				} catch (\Throwable $e) {
					// fall through to minimal dto
				}
			} else {
				$dto->candidateName = (string) ($data['full_name'] ?? $data['name'] ?? $dto->candidateName);
				$dto->candidateEmail = (string) ($data['email'] ?? '');
				$dto->jobTitle = (string) ($data['job_title'] ?? '');
				$dto->projectId = (string) ($data['project_id'] ?? '');
			}
		}
	}

	return $dto;
}

function writeTempJson(string $content): string
{
	$path = sys_get_temp_dir() . '/ra_backfill_' . uniqid('', true) . '.json';
	file_put_contents($path, $content);
	return $path;
}
