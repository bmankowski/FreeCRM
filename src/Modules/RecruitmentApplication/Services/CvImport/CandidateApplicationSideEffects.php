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

namespace App\Modules\RecruitmentApplication\Services\CvImport;

final class CandidateApplicationSideEffects
{
	private const CANDIDATES_APPLICATION_ID_MAX_LENGTH = 15;

	public static function resolveCandidate(CvApplicationDto $dto): \App\Modules\Candidates\Models\Record
	{
		CvImportLogger::log('Processing application ' . $dto->applicationNumber . ' for ' . $dto->candidateName);
		$candidateId = null;
		if ($dto->candidateName !== '' && $dto->candidateTransformedPhone !== '') {
			$candidateId = self::getCandidateIdByNameAndPhone($dto->candidateName, $dto->candidateTransformedPhone);
		}
		if ($candidateId === null && $dto->candidateName !== '' && $dto->candidateEmail !== '') {
			$candidateId = self::getCandidateIdByNameAndEmail($dto->candidateName, $dto->candidateEmail);
		}
		if ($candidateId === null) {
			return self::createNewCandidate($dto);
		}
		CvImportLogger::log('Candidate already exists: ' . $dto->candidateName);
		$candidate = \App\Modules\Candidates\Models\Record::getInstanceById($candidateId, 'Candidates');
		$candidate->set('gdpr_max_contact_date', date('Y-m-d', strtotime('+3 years')));
		$candidate->set('application_id', self::candidatesApplicationId($dto->applicationNumber));
		$candidate->save();
		return $candidate;
	}

	public static function addCommentToCandidate(\App\Modules\Candidates\Models\Record $candidate, CvApplicationDto $dto): void
	{
		if ($dto->message === '' && $dto->availability === '' && $dto->financialExpectations === '') {
			return;
		}
		$automatUserId = \App\Modules\Users\Models\Record::getUserIdByName('automat');
		$conditions = 'Dostępność: ' . $dto->availability . '<br>Oczekiwania finansowe: ' . $dto->financialExpectations . ' <br>';
		$comment = \App\Modules\Base\Models\Record::getCleanInstance('ModComments');
		$comment->set('assigned_user_id', $automatUserId);
		$comment->set('related_to', $candidate->getId());
		$messageWithPhone = '';
		if ($dto->candidateTransformedPhone === '') {
			$messageWithPhone = 'Numer kandydata nie został zaakceptowany przez system i wygląda tak:'
				. $dto->candidateOriginalPhone . '<br>';
		}
		$comment->set(
			'commentcontent',
			$messageWithPhone . $conditions . 'Treść wiadomości:<br>' . $dto->message
			. '<BR>Numer aplikacji: ' . $dto->applicationNumber
		);
		$comment->save();
	}

	public static function addCvToCandidate(\App\Modules\Candidates\Models\Record $candidate, CvApplicationDto $dto): ?\App\Modules\Base\Models\Record
	{
		$originalPath = $dto->pendingDirectory . basename($dto->originalFilename);
		$attachmentPath = $dto->cvAttachmentPath;
		if ($attachmentPath === '' || !is_file($attachmentPath)) {
			CvImportLogger::log('CV file missing on disk: ' . $attachmentPath);
			return null;
		}
		if ($attachmentPath !== $originalPath && $dto->originalFilename !== '') {
			copy($attachmentPath, $originalPath);
		}
		$parsePath = is_file($originalPath) ? $originalPath : $attachmentPath;
		if (!is_file($parsePath)) {
			CvImportLogger::log('ERROR: CV file does not exist: ' . $parsePath);
			return null;
		}
		try {
			$fileContent = substr(\App\Utils\DocumentParser::parseFromFile($parsePath), 0, 10000);
		} catch (\Exception $e) {
			CvImportLogger::log('Parse error: ' . $e->getMessage());
			$fileContent = '';
		}
		$cvContent = trim(preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $fileContent));
		$candidate->set('cv_text', $cvContent);
		$relations = DocumentHelper::prepareRelationsString('Candidates', (int) $candidate->getId());
		$documentRecord = DocumentHelper::saveAndDeleteFile($parsePath, 'CV', $relations);
		if ($documentRecord === false) {
			return null;
		}
		$candidate->transformDocumentToCV($documentRecord);
		CvFileOperations::moveToProcessed($dto);
		return $documentRecord;
	}

	public static function bindCandidateToProject(\App\Modules\Candidates\Models\Record $candidate, CvApplicationDto $dto): void
	{
		if ($dto->projectId === '') {
			\App\Log\Log::error('No project id in application');
			return;
		}
		if (self::hasCandidateAppliedForProject((string) $candidate->getId(), $dto->projectId)) {
			CvImportLogger::log('Candidate already applied to project, skipping relation');
			CvFileOperations::deleteFiles($dto);
			return;
		}
		if (!self::isProjectActive($dto->projectId)) {
			return;
		}
		$relationHandler = new \App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers();
		$relationHandler->createLink(
			(int) $dto->projectId,
			(int) $candidate->getId(),
			\App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers::STATUS_APPLIED
		);
	}

	public static function getCandidateIdByNameAndEmail(string $name, string $email): ?string
	{
		if ($email === '' || $name === '') {
			return null;
		}
		$row = (new \App\Db\Query())
			->select(['u_yf_candidates.candidatesid'])
			->from('u_yf_candidates')
			->innerJoin('u_yf_candidatescf', 'u_yf_candidatescf.candidatesid = u_yf_candidates.candidatesid')
			->innerJoin('vtiger_crmentity', 'vtiger_crmentity.crmid = u_yf_candidates.candidatesid')
			->where(['vtiger_crmentity.deleted' => 0, 'name' => $name])
			->andWhere(['or', ['u_yf_candidatescf.email_private' => $email], ['u_yf_candidatescf.email_business' => $email]])
			->one();
		return isset($row['candidatesid']) ? (string) $row['candidatesid'] : null;
	}

	public static function getCandidateIdByNameAndPhone(string $name, string $phone): ?string
	{
		if ($phone === '' || $name === '') {
			return null;
		}
		$row = (new \App\Db\Query())
			->select(['u_yf_candidates.candidatesid'])
			->from('u_yf_candidates')
			->innerJoin('vtiger_crmentity', 'vtiger_crmentity.crmid = u_yf_candidates.candidatesid')
			->where(['vtiger_crmentity.deleted' => 0, 'phone' => $phone, 'name' => $name])
			->one();
		return isset($row['candidatesid']) ? (string) $row['candidatesid'] : null;
	}

	public static function getSourceName(string $sourceId): string
	{
		if ($sourceId === '') {
			return 'WWW ITC';
		}
		$sourceName = (new \App\Db\Query())
			->select(['z.application_source'])
			->from(['z' => 'vtiger_application_source'])
			->where(['z.application_sourceid' => $sourceId])
			->scalar();
		return $sourceName ?: 'WWW ITC';
	}

	private static function createNewCandidate(CvApplicationDto $dto): \App\Modules\Candidates\Models\Record
	{
		if ($dto->candidateName === '') {
			throw new \RuntimeException('Candidate name is empty');
		}
		CvImportLogger::log('Creating new candidate ' . $dto->candidateName);
		$candidate = \App\Modules\Base\Models\Record::getCleanInstance('Candidates');
		$candidate->set('name', $dto->candidateName);
		$candidate->set('phone', $dto->candidateTransformedPhone);
		$candidate->set('candidate_status', 'Kandydat');
		$candidate->set('email_private', $dto->candidateEmail);
		$candidate->set('application_id', self::candidatesApplicationId($dto->applicationNumber));
		$candidate->set('application_source', self::getSourceName($dto->sourceId));
		$allowed = self::isFutureContactAllowed($dto->agreeToContact);
		$candidate->set('is_future_contact_allowed', $allowed ? 1 : 0);
		$candidate->set(
			'gdpr_max_contact_date',
			date('Y-m-d', strtotime($allowed ? '+3 years' : '+9 months'))
		);
		$candidate->set('application_json_content', $dto->rawJsonData);
		$candidate->set('is_referred_by_employee', $dto->isReferredByEmployee ? 1 : 0);
		if ($dto->isReferredByEmployee) {
			$candidate->set('referred_by_employee', $dto->referredByEmployee);
			$consultant = self::getConsultantByEmail($dto->referredByEmail)
				?? self::getConsultantByName($dto->referredByEmployee);
			$candidate->set('referrer_consultant_id', $consultant);
			$candidate->set('referred_on_position', $dto->referredOnPosition);
			$candidate->set('referred_by_email', $dto->referredByEmail);
		}
		$candidate->save();
		return \App\Modules\Candidates\Models\Record::getInstanceById($candidate->getId(), 'Candidates');
	}

	private static function isFutureContactAllowed(string $value): bool
	{
		$normalized = strtolower(trim($value));
		return in_array($normalized, ['tak', 'yes', 'true', '1'], true);
	}

	private static function getConsultantByEmail(string $email): ?int
	{
		if ($email === '') {
			return null;
		}
		$id = (new \App\Db\Query())
			->select(['k.konsultanciid'])
			->from(['k' => 'u_yf_konsultanci'])
			->innerJoin('vtiger_crmentity e', 'k.konsultanciid = e.crmid')
			->where(['e.deleted' => 0])
			->andWhere(['or', ['k.email_private' => $email], ['k.email_business' => $email]])
			->scalar();
		return $id ? (int) $id : null;
	}

	private static function getConsultantByName(string $name): ?int
	{
		if ($name === '') {
			return null;
		}
		$parts = explode(' ', trim($name));
		$reversed = count($parts) >= 2 ? implode(' ', array_reverse($parts)) : $name;
		$id = (new \App\Db\Query())
			->select(['k.konsultanciid'])
			->from(['k' => 'u_yf_konsultanci'])
			->innerJoin('vtiger_crmentity e', 'k.konsultanciid = e.crmid')
			->where(['e.deleted' => 0])
			->andWhere(['or', ['k.name' => $name], ['k.name' => $reversed]])
			->scalar();
		return $id ? (int) $id : null;
	}

	private static function hasCandidateAppliedForProject(string $candidateId, string $projectId): bool
	{
		if ($projectId === '') {
			return false;
		}
		return (new \App\Db\Query())
			->from('u_yf_projekty_rekrutacyjne_relations_members_entity r')
			->innerJoin('vtiger_crmentity e1', 'e1.crmid = r.crmid')
			->innerJoin('vtiger_crmentity e2', 'e2.crmid = r.relcrmid')
			->where(['e1.deleted' => 0, 'e2.deleted' => 0, 'r.crmid' => $projectId, 'r.relcrmid' => $candidateId])
			->exists();
	}

	private static function isProjectActive(string $projectId): bool
	{
		if ($projectId === '') {
			return false;
		}
		return (new \App\Db\Query())
			->from('u_yf_projektyrekrutacyjne p')
			->innerJoin('vtiger_crmentity e', 'e.crmid = p.projektyrekrutacyjneid')
			->where(['e.deleted' => 0, 'p.projektyrekrutacyjneid' => $projectId])
			->andWhere(['in', 'p.etap_sprzedazy', ['Aktywna', 'Oczekiwanie na wybór kandydatów']])
			->exists();
	}

	private static function candidatesApplicationId(string $applicationNumber): string
	{
		return substr($applicationNumber, 0, self::CANDIDATES_APPLICATION_ID_MAX_LENGTH);
	}
}
