<?php

declare(strict_types=1);

namespace App\Modules\ProjektyRekrutacyjne\Services;

class RecruitmentProjectsDashboard
{
	/** @var list<string> Candidate status columns shown on the projects dashboard (chip columns). */
	private const DASHBOARD_STATUS_COLUMNS = [
		'PPL_TO_BE_SENT_TO_CLIENT',
		'PPL_HANDED_TO_SALES',
		'PPL_SENT_TO_CLIENT',
		'PPL_STAGE_1',
		'PPL_STAGE_2',
		'PPL_STAGE_3',
		'PPL_ACCEPTED',
	];

	/** @return list<string> */
	public static function getStatusColumns(): array
	{
		return self::DASHBOARD_STATUS_COLUMNS;
	}

	/** @return list<array<string, mixed>> */
	public static function getRows(): array
	{
		$projects = self::fetchActiveProjects();
		if ($projects === []) {
			return [];
		}

		$projectIds = array_map(static fn (array $row): int => (int) $row['id'], $projects);
		$candidatesByProject = self::fetchCandidatesByProject($projectIds);

		$rows = [];
		foreach ($projects as $project) {
			$projectId = (int) $project['id'];
			$rows[] = [
				'id' => $projectId,
				'name' => (string) $project['nazwa_projektu'],
				'clientName' => (string) ($project['client_name'] ?? ''),
				'clientUrl' => self::buildDetailUrl('Accounts', (int) ($project['kontrahent'] ?? 0)),
				'ownerName' => (string) ($project['owner_name'] ?? ''),
				'detailUrl' => self::buildDetailUrl('ProjektyRekrutacyjne', $projectId),
				'appliedCount' => (int) ($project['cvs_applied_number'] ?? 0),
				'candidates' => $candidatesByProject[$projectId] ?? self::emptyCandidates(),
			];
		}

		return $rows;
	}

	public static function getStatusTransitionsJson(): string
	{
		$payload = [
			'configured' => RecruitmentStatusTransition::isConfigured(),
			'transitions' => RecruitmentStatusTransition::getAdjacencyMap(),
		];

		return \App\Security\Purifier::encodeHtml(\App\Utils\Json::encode($payload));
	}

	/** @return list<array<string, mixed>> */
	private static function fetchActiveProjects(): array
	{
		return (new \App\Db\Query())
			->select([
				'id' => 'p.projektyrekrutacyjneid',
				'p.nazwa_projektu',
				'p.kontrahent',
				'p.cvs_applied_number',
				'client_name' => 'a.accountname',
				'owner_name' => new \yii\db\Expression(
					"COALESCE(NULLIF(TRIM(CONCAT(u.first_name, ' ', u.last_name)), ''), u.user_name, g.groupname, '')"
				),
			])
			->from(['p' => 'u_yf_projektyrekrutacyjne'])
			->innerJoin(['e' => 'vtiger_crmentity'], 'e.crmid = p.projektyrekrutacyjneid')
			->leftJoin(['a' => 'vtiger_account'], 'a.accountid = p.kontrahent')
			->leftJoin(['ae' => 'vtiger_crmentity'], 'ae.crmid = a.accountid AND ae.deleted = 0')
			->leftJoin(['u' => 'vtiger_users'], 'u.id = e.smownerid')
			->leftJoin(['g' => 'vtiger_groups'], 'g.groupid = e.smownerid')
			->where(['e.deleted' => 0, 'p.etap_sprzedazy' => 'Aktywna'])
			->orderBy(['a.accountname' => SORT_ASC, 'p.nazwa_projektu' => SORT_ASC])
			->all();
	}

	/**
	 * @param list<int> $projectIds
	 * @return array<int, array<string, list<array{id:int,name:string,url:string}>>>
	 */
	private static function fetchCandidatesByProject(array $projectIds): array
	{
		if ($projectIds === []) {
			return [];
		}

		$rows = (new \App\Db\Query())
			->select([
				'project_id' => 'rel.crmid',
				'candidate_id' => 'rel.relcrmid',
				'rel.recruitment_status_rel',
				'candidate_name' => 'c.name',
			])
			->from(['rel' => 'u_yf_projekty_rekrutacyjne_relations_members_entity'])
			->innerJoin(['pe' => 'vtiger_crmentity'], 'pe.crmid = rel.crmid AND pe.deleted = 0')
			->innerJoin(['ce' => 'vtiger_crmentity'], 'ce.crmid = rel.relcrmid AND ce.deleted = 0')
			->innerJoin(['c' => 'u_yf_candidates'], 'c.candidatesid = rel.relcrmid')
			->where(['rel.crmid' => $projectIds])
			->andWhere(['rel.recruitment_status_rel' => self::DASHBOARD_STATUS_COLUMNS])
			->orderBy(['c.name' => SORT_ASC])
			->all();

		$grouped = [];
		foreach ($rows as $row) {
			$projectId = (int) $row['project_id'];
			$status = (string) $row['recruitment_status_rel'];
			$candidateId = (int) $row['candidate_id'];

			if (!isset($grouped[$projectId])) {
				$grouped[$projectId] = self::emptyCandidates();
			}

			$grouped[$projectId][$status][] = [
				'id' => $candidateId,
				'name' => (string) $row['candidate_name'],
				'url' => self::buildDetailUrl('Candidates', $candidateId),
			];
		}

		return $grouped;
	}

	/** @return array<string, list<array{id:int,name:string,url:string}>> */
	private static function emptyCandidates(): array
	{
		return array_fill_keys(self::DASHBOARD_STATUS_COLUMNS, []);
	}

	private static function buildDetailUrl(string $module, int $recordId): string
	{
		if ($recordId <= 0) {
			return '';
		}

		return 'index.php?module=' . $module . '&view=Detail&record=' . $recordId;
	}
}
