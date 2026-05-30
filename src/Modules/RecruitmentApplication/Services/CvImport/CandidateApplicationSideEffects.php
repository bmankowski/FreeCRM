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
	private const KANDYDACI_APPLICATION_ID_MAX_LENGTH = 15;

	public static function resolveCandidate(CvApplicationDto $dto): \App\Modules\Kandydaci\Models\Record
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
		$candidate = \App\Modules\Kandydaci\Models\Record::getInstanceById($candidateId, 'Kandydaci');
		$candidate->set('data_maksymalny_kontakt_rodo', date('Y-m-d', strtotime('+3 years')));
		$candidate->set('application_id', self::kandydaciApplicationId($dto->applicationNumber));
		$candidate->save();
		return $candidate;
	}

	public static function addCommentToCandidate(\App\Modules\Kandydaci\Models\Record $candidate, CvApplicationDto $dto): void
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

	public static function addCvToCandidate(\App\Modules\Kandydaci\Models\Record $candidate, CvApplicationDto $dto): ?\App\Modules\Base\Models\Record
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
		$candidate->set('tresc_cv', $cvContent);
		$relations = DocumentHelper::prepareRelationsString('Kandydaci', (int) $candidate->getId());
		$documentRecord = DocumentHelper::saveAndDeleteFile($parsePath, 'CV', $relations);
		if ($documentRecord === false) {
			return null;
		}
		$candidate->transformDocumentToCV($documentRecord);
		CvFileOperations::moveToProcessed($dto);
		return $documentRecord;
	}

	public static function bindCandidateToProject(\App\Modules\Kandydaci\Models\Record $candidate, CvApplicationDto $dto): void
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
		$projectModule = \App\Modules\Base\Models\Module::getInstance('ProjektyRekrutacyjne');
		$relationModel = \App\Modules\Base\Models\Relation::getInstance($projectModule, $candidate->getModule());
		if ($relationModel !== false) {
			$relationModel->addRelation((int) $dto->projectId, (int) $candidate->getId());
		}
	}

	public static function getCandidateIdByNameAndEmail(string $name, string $email): ?string
	{
		if ($email === '' || $name === '') {
			return null;
		}
		$row = (new \App\Db\Query())
			->select(['u_yf_kandydaci.kandydaciid'])
			->from('u_yf_kandydaci')
			->innerJoin('u_yf_kandydacicf', 'u_yf_kandydacicf.kandydaciid = u_yf_kandydaci.kandydaciid')
			->innerJoin('vtiger_crmentity', 'vtiger_crmentity.crmid = u_yf_kandydaci.kandydaciid')
			->where(['vtiger_crmentity.deleted' => 0, 'name' => $name])
			->andWhere(['or', ['u_yf_kandydacicf.email_prywatny' => $email], ['u_yf_kandydacicf.email_firmowy' => $email]])
			->one();
		return isset($row['kandydaciid']) ? (string) $row['kandydaciid'] : null;
	}

	public static function getCandidateIdByNameAndPhone(string $name, string $phone): ?string
	{
		if ($phone === '' || $name === '') {
			return null;
		}
		$row = (new \App\Db\Query())
			->select(['u_yf_kandydaci.kandydaciid'])
			->from('u_yf_kandydaci')
			->innerJoin('vtiger_crmentity', 'vtiger_crmentity.crmid = u_yf_kandydaci.kandydaciid')
			->where(['vtiger_crmentity.deleted' => 0, 'telefon' => $phone, 'name' => $name])
			->one();
		return isset($row['kandydaciid']) ? (string) $row['kandydaciid'] : null;
	}

	public static function getSourceName(string $sourceId): string
	{
		if ($sourceId === '') {
			return 'WWW ITC';
		}
		$sourceName = (new \App\Db\Query())
			->select(['vtiger_zrodlo_aplikacji.zrodlo_aplikacji'])
			->from('vtiger_zrodlo_aplikacji')
			->where(['zrodlo_aplikacjiid' => $sourceId])
			->scalar();
		return $sourceName ?: 'WWW ITC';
	}

	private static function createNewCandidate(CvApplicationDto $dto): \App\Modules\Kandydaci\Models\Record
	{
		if ($dto->candidateName === '') {
			throw new \RuntimeException('Candidate name is empty');
		}
		CvImportLogger::log('Creating new candidate ' . $dto->candidateName);
		$candidate = \App\Modules\Base\Models\Record::getCleanInstance('Kandydaci');
		$candidate->set('name', $dto->candidateName);
		$candidate->set('telefon', $dto->candidateTransformedPhone);
		$candidate->set('status_kandydata', 'Kandydat');
		$candidate->set('email_prywatny', $dto->candidateEmail);
		$candidate->set('application_id', self::kandydaciApplicationId($dto->applicationNumber));
		$candidate->set('zrodlo_aplikacji', self::getSourceName($dto->sourceId));
		$allowed = self::isFutureContactAllowed($dto->agreeToContact);
		$candidate->set('is_future_contact_allowed', $allowed ? 1 : 0);
		$candidate->set(
			'data_maksymalny_kontakt_rodo',
			date('Y-m-d', strtotime($allowed ? '+3 years' : '+9 months'))
		);
		$candidate->set('application_json_content', $dto->rawJsonData);
		$candidate->set('is_referred_by_employee', $dto->isReferredByEmployee ? 1 : 0);
		if ($dto->isReferredByEmployee) {
			$candidate->set('referred_by_employee', $dto->referredByEmployee);
			$consultant = self::getConsultantByEmail($dto->referredByEmail)
				?? self::getConsultantByName($dto->referredByEmployee);
			$candidate->set('polec_znajomego', $consultant);
			$candidate->set('referred_on_position', $dto->referredOnPosition);
			$candidate->set('referred_by_email', $dto->referredByEmail);
		}
		$candidate->save();
		return \App\Modules\Kandydaci\Models\Record::getInstanceById($candidate->getId(), 'Kandydaci');
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
			->andWhere(['or', ['k.email_prywatny' => $email], ['k.email_firmowy' => $email]])
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

	private static function kandydaciApplicationId(string $applicationNumber): string
	{
		return substr($applicationNumber, 0, self::KANDYDACI_APPLICATION_ID_MAX_LENGTH);
	}
}
