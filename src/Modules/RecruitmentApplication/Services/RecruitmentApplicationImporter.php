<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\RecruitmentApplication\Services;

use App\Modules\RecruitmentApplication\Services\CvImport\ApplicationImportRepository;
use App\Modules\RecruitmentApplication\Services\CvImport\ApplicationNumberResolver;
use App\Modules\RecruitmentApplication\Services\CvImport\CandidateApplicationSideEffects;
use App\Modules\RecruitmentApplication\Services\CvImport\CvApplicationDto;
use App\Modules\RecruitmentApplication\Services\CvImport\CvFileOperations;
use App\Modules\RecruitmentApplication\Services\CvImport\CvFilePaths;
use App\Modules\RecruitmentApplication\Services\CvImport\CvImportLock;
use App\Modules\RecruitmentApplication\Services\CvImport\CvImportLogger;
use App\Modules\RecruitmentApplication\Services\CvImport\CvJsonParser;
use App\Modules\RecruitmentApplication\Services\CvImport\ImportErrorMailer;
use App\Modules\RecruitmentApplication\Services\CvImport\PhoneNormalizer;

final class RecruitmentApplicationImporter
{
	public function importPending(?int $limit = null): void
	{
		if ($limit === null) {
			$envLimit = getenv('CV_IMPORT_LIMIT');
			if ($envLimit !== false && $envLimit !== '') {
				$limit = max(1, (int) $envLimit);
			}
		}
		$lock = new CvImportLock();
		if (!$lock->acquire()) {
			CvImportLogger::log('Lock acquisition failed, CV import already running, skipping');
			return;
		}
		try {
			$this->processPendingFiles($limit);
		} finally {
			$lock->release();
		}
	}

	public function isApplicationImported(string $applicationNumber): bool
	{
		return ApplicationImportRepository::isApplicationImported($applicationNumber);
	}

	public function createFromDto(CvApplicationDto $dto): \App\Modules\Base\Models\Record
	{
		$automatUserId = \App\Modules\Users\Models\Record::getUserIdByName('automat');
		$record = \App\Modules\Base\Models\Record::getCleanInstance('RecruitmentApplication');
		$record->set('application_number', $dto->applicationNumber);
		$record->set('assigned_user_id', $automatUserId);
		self::mapDtoToRecord($record, $dto);
		$record->save();
		self::persistApplicationNumber($record->getId(), $dto->applicationNumber);
		return $record;
	}

	public static function mapDtoToRecord(\App\Modules\Base\Models\Record $record, CvApplicationDto $dto): void
	{
		$record->set('cf_303291', $dto->candidateName);
		$record->set('cf_303283', $dto->candidateEmail);
		$record->set('cf_303293', $dto->candidateOriginalPhone);
		if (PhoneNormalizer::isValidE164($dto->candidateTransformedPhone)) {
			$record->set('cf_303285', $dto->candidateTransformedPhone);
		}
		$record->set('cf_303295', $dto->jobTitle);
		if ($dto->projectId !== '' && is_numeric($dto->projectId)) {
			$record->set('cf_303297', (int) $dto->projectId);
		}
		if ($dto->sourceId !== '' && is_numeric($dto->sourceId)) {
			$record->set('cf_303299', (int) $dto->sourceId);
		}
		$record->set('cf_303339', $dto->formType);
		$record->set('cf_303325', $dto->submittedAt);
		$record->set('cf_303287', $dto->message);
		$record->set('cf_303311', $dto->availability);
		$record->set('cf_303313', $dto->preferredContractType);
		$record->set('cf_303315', $dto->expectedSalary);
		$record->set('cf_303317', self::consentToInt($dto->agreeToContact));
		$record->set('cf_303319', $dto->cvOriginalFilename);
		$record->set('cf_303321', $dto->cvSavedFilename);
		$record->set('cf_303323', $dto->cvAttachmentUrl);
		$record->set('cf_303327', $dto->rawJsonData);
		$record->set('cf_303329', $dto->isReferredByEmployee ? 1 : 0);
		$record->set('cf_303331', $dto->referredByEmployee);
		$record->set('cf_303333', $dto->referredByEmail);
		$record->set('cf_303335', $dto->referredOnPosition);
		$record->set('cf_303305', $dto->postId !== '' && is_numeric($dto->postId) ? (int) $dto->postId : null);
		$record->set('cf_303307', $dto->formId !== '' && is_numeric($dto->formId) ? (int) $dto->formId : null);
		$record->set('cf_303309', $dto->referrerUrl);
		$record->set('cf_303341', $dto->formLanguage);
	}

	private function processPendingFiles(?int $limit = null): void
	{
		CvImportLogger::log('Starting CV application import');
		$pending = CvFilePaths::pending();
		$jsonFiles = glob($pending . '*.json') ?: [];
		sort($jsonFiles);
		if ($limit !== null) {
			$jsonFiles = array_slice($jsonFiles, 0, $limit);
			CvImportLogger::log('Processing at most ' . $limit . ' JSON file(s)');
		}
		foreach ($jsonFiles as $jsonFilePath) {
			$dto = null;
			try {
				CvImportLogger::log('Importing from file: ' . $jsonFilePath);
				$applicationNumber = ApplicationNumberResolver::fromJsonPath($jsonFilePath);
				if ($this->isApplicationImported($applicationNumber)) {
					CvImportLogger::log('Application already imported: ' . $applicationNumber);
					$dto = CvJsonParser::parseFile($pending, $jsonFilePath, $applicationNumber);
					CvFileOperations::deleteFiles($dto);
					continue;
				}
				$dto = CvJsonParser::parseFile($pending, $jsonFilePath, $applicationNumber);
				$application = $this->createFromDto($dto);
				$candidate = CandidateApplicationSideEffects::resolveCandidate($dto);
				CandidateApplicationSideEffects::addCommentToCandidate($candidate, $dto);
				$candidate->save();
				$document = CandidateApplicationSideEffects::addCvToCandidate($candidate, $dto);
				$candidate->save();
				CandidateApplicationSideEffects::bindCandidateToProject($candidate, $dto);
				$candidate->save();
				$application->set('cf_303289', (int) $candidate->getId());
				if ($document !== null) {
					$application->set('cf_303337', (int) $document->getId());
				}
				if ($dto->projectId !== '' && is_numeric($dto->projectId)) {
					$application->set('cf_303297', (int) $dto->projectId);
				}
				$application->save();
				CvFileOperations::deleteFiles($dto);
			} catch (\Throwable $e) {
				ImportErrorMailer::record($dto, $e);
				if ($dto !== null) {
					CvFileOperations::moveToFailed($dto);
				}
				\App\Log\Log::error($e);
			}
		}
		ImportErrorMailer::sendSummaryIfAny();
		CvImportLogger::log('CV application import finished');
	}

	private static function consentToInt(string $value): int
	{
		$normalized = strtolower(trim($value));
		return in_array($normalized, ['tak', 'yes', 'true', '1'], true) ? 1 : 0;
	}

	private static function persistApplicationNumber(int $recordId, string $applicationNumber): void
	{
		if ($applicationNumber === '') {
			return;
		}
		$db = \App\Db\Db::getInstance();
		$db->createCommand()
			->update(
				'vtiger_recruitmentapplication',
				['application_number' => $applicationNumber],
				['recruitmentapplicationid' => $recordId]
			)
			->execute();
		\App\Records\Record::updateLabel('RecruitmentApplication', $recordId);
	}
}
