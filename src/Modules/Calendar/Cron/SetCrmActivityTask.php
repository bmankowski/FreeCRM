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

namespace App\Modules\Calendar\Cron;

use App\Modules\Cron\Tasks\AbstractCronTask;

final class SetCrmActivityTask extends AbstractCronTask
{
	public function execute(): void
	{
		$dataReader = (new \App\Db\Query())->select(['vtiger_crmentity.crmid', 'vtiger_crmentity.setype'])
			->from('vtiger_crmentity')
			->innerJoin('vtiger_entity_stats', 'vtiger_entity_stats.crmid = vtiger_crmentity.crmid')
			->where(['and', ['vtiger_crmentity.deleted' => 0], ['not', ['vtiger_entity_stats.crmactivity' => null]]])
			->limit(\App\Core\AppConfig::module('Calendar', 'CRON_MAX_NUMBERS_ACTIVITY_STATS'))
			->createCommand()->query();
		while ($row = $dataReader->read()) {
			\App\Modules\Calendar\Models\Record::setCrmActivity(array_flip([$row['crmid']]), $row['setype']);
		}
	}
}
