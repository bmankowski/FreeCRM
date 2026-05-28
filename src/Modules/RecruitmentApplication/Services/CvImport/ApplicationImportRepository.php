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
				'ra.application_number' => $applicationNumber,
			])
			->exists();
	}
}
