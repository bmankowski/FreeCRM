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

namespace App\Modules\ProjektyRekrutacyjne\Services;

use App\Modules\Base\Models\ListView;
use App\Modules\Candidates\Services\CvSkillsSearch;
use App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers;

class KanbanCandidatePicker
{
	/**
	 * @return list<string>
	 */
	public static function listFieldNames(): array
	{
		return ['id', 'name', 'phone'];
	}

	public static function createListViewModel(int $projectId, string $cvSkills): ListView
	{
		$listViewModel = ListView::getInstanceForPopup('Candidates', 'ProjektyRekrutacyjne');
		$queryGenerator = $listViewModel->getQueryGenerator();
		$queryGenerator->setFields(self::listFieldNames());

		$conditions = CvSkillsSearch::buildSearchParamConditions($cvSkills);
		if ($conditions !== []) {
			$listViewModel->set(
				'search_params',
				$queryGenerator->parseBaseSearchParamsToCondition([$conditions])
			);
		}

		self::excludeProjectMembers($queryGenerator, $projectId);

		return $listViewModel;
	}

	/**
	 * @param list<int> $candidateIds
	 * @return array<int, string> candidateId => comma-separated nazwa_projektu values
	 */
	public static function projectNamesByCandidateIds(array $candidateIds): array
	{
		$ids = array_values(array_filter(
			array_map(static fn ($id): int => (int) $id, $candidateIds),
			static fn (int $id): bool => $id > 0
		));
		if ($ids === []) {
			return [];
		}

		$rows = (new \App\Db\Query())
			->select([
				'rel.relcrmid',
				"GROUP_CONCAT(DISTINCT u.nazwa_projektu ORDER BY u.nazwa_projektu SEPARATOR ', ') AS project_names",
			])
			->from(GetRelatedMembers::TABLE_NAME . ' rel')
			->innerJoin('vtiger_crmentity e', 'e.crmid = rel.crmid AND e.deleted = 0')
			->innerJoin('u_yf_projektyrekrutacyjne u', 'u.projektyrekrutacyjneid = rel.crmid')
			->where(['rel.relcrmid' => $ids])
			->groupBy('rel.relcrmid')
			->all();

		$map = [];
		foreach ($rows as $row) {
			$map[(int) $row['relcrmid']] = (string) ($row['project_names'] ?? '');
		}

		return $map;
	}

	private static function excludeProjectMembers(\App\QueryField\QueryGenerator $queryGenerator, int $projectId): void
	{
		if ($projectId <= 0) {
			return;
		}

		$linkedIds = (new \App\Db\Query())
			->select('relcrmid')
			->from(GetRelatedMembers::TABLE_NAME)
			->where(['crmid' => $projectId])
			->column();

		if ($linkedIds === []) {
			return;
		}

		$queryGenerator->addNativeCondition(['not in', 'vtiger_crmentity.crmid', $linkedIds]);
	}
}
