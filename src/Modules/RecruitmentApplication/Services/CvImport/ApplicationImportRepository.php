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

final class ApplicationImportRepository
{
	public static function isApplicationImported(string $applicationNumber): bool
	{
		if ($applicationNumber === '') {
			return false;
		}
		return (new \App\Db\Query())
			->from(['ra' => 'vtiger_recruitmentapplication'])
			->innerJoin(['e' => 'vtiger_crmentity'], 'e.crmid = ra.recruitmentapplicationid')
			->where([
				'e.deleted' => 0,
				'e.setype' => 'RecruitmentApplication',
				'ra.application_number' => $applicationNumber,
			])
			->exists();
	}

	/**
	 * @return list<int>
	 */
	public static function fetchApplicationIdsWithoutCandidate(?int $limit = null): array
	{
		$query = (new \App\Db\Query())
			->select(['ra.recruitmentapplicationid'])
			->from(['ra' => 'vtiger_recruitmentapplication'])
			->innerJoin(['e' => 'vtiger_crmentity'], 'e.crmid = ra.recruitmentapplicationid')
			->innerJoin(['cf' => 'vtiger_recruitmentapplicationcf'], 'cf.recruitmentapplicationid = ra.recruitmentapplicationid')
			->where([
				'e.deleted' => 0,
				'e.setype' => 'RecruitmentApplication',
			])
			->andWhere(['or', ['cf.candidate_id' => null], ['cf.candidate_id' => 0]])
			->andWhere(['and', ['not', ['ra.application_number' => null]], ['!=', 'ra.application_number', '']])
			->orderBy(['e.createdtime' => SORT_ASC]);
		if ($limit !== null) {
			$query->limit($limit);
		}
		$rows = $query->column();
		return array_map('intval', $rows);
	}
}
