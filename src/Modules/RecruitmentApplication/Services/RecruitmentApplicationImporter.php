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
use App\Modules\RecruitmentApplication\Services\CvImport\CvImportLogger;
use App\Modules\RecruitmentApplication\Services\CvImport\CvJsonParser;
use App\Modules\RecruitmentApplication\Services\CvImport\ImportErrorMailer;
use App\Modules\RecruitmentApplication\Services\CvImport\PhoneNormalizer;

final class RecruitmentApplicationImporter
{
	public function importApplicationsFromPending(?int $limit = null): void
	{
		$this->processPendingApplicationFiles($this->resolveImportLimit($limit));
	}

	public function importCandidatesFromApplications(?int $limit = null): void
	{
		$this->processApplicationsWithoutCandidate($this->resolveImportLimit($limit));
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
		$id = (int) $record->getId();
		self::persistApplicationNumber($id, $dto->applicationNumber);
		return \App\Modules\Base\Models\Record::getInstanceById($id, 'RecruitmentApplication');
	}

	public static function mapDtoToRecord(\App\Modules\Base\Models\Record $record, CvApplicationDto $dto): void
	{
		$record->set('candidate_name', $dto->candidateName);
		$record->set('candidate_email', $dto->candidateEmail);
		$record->set('phone_raw', $dto->candidateOriginalPhone);
		if (PhoneNormalizer::isValidE164($dto->candidateTransformedPhone)) {
			$record->set('candidate_phone', $dto->candidateTransformedPhone);
		}
		$record->set('job_title', $dto->jobTitle);
		if ($dto->projectId !== '' && is_numeric($dto->projectId)) {
			$record->set('project_id', (int) $dto->projectId);
		}
		if ($dto->sourceId !== '' && is_numeric($dto->sourceId)) {
			$record->set('source_id', (int) $dto->sourceId);
		}
		$record->set('form_type', $dto->formType);
		$record->set('submitted_at', $dto->submittedAt);
		$record->set('message', $dto->message);
		$record->set('available_from', $dto->availability);
		$record->set('preferred_contract_type', $dto->preferredContractType);
		$record->set('expected_salary', $dto->expectedSalary);
		$record->set('future_recruitment_consent', self::consentToInt($dto->agreeToContact));
		$record->set('cv_original_filename', $dto->cvOriginalFilename);
		$record->set('cv_saved_filename', $dto->cvSavedFilename);
		$record->set('cv_attachment_url', $dto->cvAttachmentUrl);
		$record->set('application_json_content', $dto->rawJsonData);
		$record->set('is_referred_by_employee', $dto->isReferredByEmployee ? 1 : 0);
		$record->set('referred_by_employee', $dto->referredByEmployee);
		$record->set('referred_by_email', $dto->referredByEmail);
		$record->set('referred_on_position', $dto->referredOnPosition);
		$record->set('post_id', $dto->postId !== '' && is_numeric($dto->postId) ? (int) $dto->postId : null);
		$record->set('form_id', $dto->formId !== '' && is_numeric($dto->formId) ? (int) $dto->formId : null);
		$record->set('referrer_url', $dto->referrerUrl);
		$record->set('form_language', $dto->formLanguage);
	}

	private function processPendingApplicationFiles(?int $limit): void
	{
		CvImportLogger::log('Starting CV application ingest');
		$pending = CvFilePaths::pending();
		$jsonFiles = glob($pending . '*.json') ?: [];
		sort($jsonFiles);
		if ($limit !== null) {
			$jsonFiles = array_slice($jsonFiles, 0, $limit);
			CvImportLogger::log('Processing at most ' . $limit . ' JSON file(s)');
		}
		foreach ($jsonFiles as $jsonFilePath) {
			$dto = null;
			$applicationNumber = '';
			try {
				CvImportLogger::log('Importing from file: ' . $jsonFilePath);
				$applicationNumber = ApplicationNumberResolver::fromJsonPath($jsonFilePath);
				if ($this->isApplicationImported($applicationNumber)) {
					CvImportLogger::log('Application already imported: ' . $applicationNumber);
					$dto = CvJsonParser::parseFile($pending, $jsonFilePath, $applicationNumber);
					CvFileOperations::moveToProcessed($dto);
					continue;
				}
				$dto = CvJsonParser::parseFile($pending, $jsonFilePath, $applicationNumber);
				$application = $this->createFromDto($dto);
				CandidateApplicationSideEffects::addCvToApplication($application, $dto);
				CvFileOperations::moveToProcessed($dto);
			} catch (\yii\db\IntegrityException $e) {
				if ($applicationNumber !== ''
					&& str_contains($e->getMessage(), 'uq_recruitmentapplication_application_number')) {
					CvImportLogger::log('Application already imported (concurrent): ' . $applicationNumber);
					if ($dto !== null) {
						CvFileOperations::moveToProcessed($dto);
					}
					continue;
				}
				ImportErrorMailer::record($dto, $e);
				if ($dto !== null) {
					CvFileOperations::moveToFailed($dto);
				}
				\App\Log\Log::error($e);
			} catch (\Throwable $e) {
				ImportErrorMailer::record($dto, $e);
				if ($dto !== null) {
					CvFileOperations::moveToFailed($dto);
				}
				\App\Log\Log::error($e);
			}
		}
		ImportErrorMailer::sendSummaryIfAny();
		CvImportLogger::log('CV application ingest finished');
	}

	private function processApplicationsWithoutCandidate(?int $limit): void
	{
		CvImportLogger::log('Starting CV candidate materialization');
		$applicationIds = ApplicationImportRepository::fetchApplicationIdsWithoutCandidate($limit);
		if ($limit !== null) {
			CvImportLogger::log('Processing at most ' . $limit . ' application(s)');
		}
		$processed = CvFilePaths::processed();
		foreach ($applicationIds as $applicationId) {
			try {
				$application = \App\Modules\Base\Models\Record::getInstanceById($applicationId, 'RecruitmentApplication');
				if ((int) $application->get('candidate_id') > 0) {
					continue;
				}
				$applicationNumber = (string) $application->get('application_number');
				$rawJson = (string) $application->get('application_json_content');
				if ($rawJson === '') {
					CvImportLogger::log('Missing application_json_content for application ' . $applicationNumber);
					continue;
				}
				CvImportLogger::log('Materializing application ' . $applicationNumber);
				$dto = CvJsonParser::parseJsonContent($processed, $applicationNumber, $rawJson);
				$dto->pendingDirectory = $processed;
				$cvSaved = (string) $application->get('cv_saved_filename');
				if ($cvSaved !== '') {
					$dto->cvSavedFilename = $cvSaved;
				}
				$cvOriginal = (string) $application->get('cv_original_filename');
				if ($cvOriginal !== '') {
					$dto->originalFilename = $cvOriginal;
					$dto->cvOriginalFilename = $cvOriginal;
				}
				$cvDocumentId = (int) $application->get('cv_document_id');
				$candidate = CandidateApplicationSideEffects::resolveCandidate($dto);
				$candidateId = (int) $candidate->getId();
				$application->set('candidate_id', $candidateId);
				$application->save();
				CandidateApplicationSideEffects::addCommentToCandidate($candidate, $dto);
				$candidate->save();
				CandidateApplicationSideEffects::linkApplicationCvToCandidate($candidate, $cvDocumentId);
				$candidate->save();
				CandidateApplicationSideEffects::bindCandidateToProject($candidate, $dto);
				$candidate->save();
			} catch (\Throwable $e) {
				ImportErrorMailer::record(null, $e);
				\App\Log\Log::error($e);
			}
		}
		ImportErrorMailer::sendSummaryIfAny();
		CvImportLogger::log('CV candidate materialization finished');
	}

	private function resolveImportLimit(?int $limit): ?int
	{
		if ($limit !== null) {
			return $limit;
		}
		$envLimit = getenv('CV_IMPORT_LIMIT');
		if ($envLimit === false || $envLimit === '') {
			return null;
		}
		return max(1, (int) $envLimit);
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
