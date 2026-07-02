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
