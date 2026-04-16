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

namespace App\Modules\Events\Cron;

use App\Modules\Cron\Tasks\AbstractCronTask;

final class RecurringEventsTask extends AbstractCronTask
{
	public function execute(): void
	{
		$dataReader = (new \App\Db\Query())->select(['followup'])
				->from('vtiger_activity')
				->innerJoin('vtiger_crmentity', 'vtiger_crmentity.crmid = vtiger_activity.activityid')
				->where([
					'and',
					['vtiger_crmentity.deleted' => 0],
					['vtiger_crmentity.setype' => 'Calendar'],
					['vtiger_activity.reapeat' => 1],
					['NOT', ['vtiger_activity.recurrence' => null]],
				['not like', 'vtiger_activity.recurrence', ['UNTIL', 'COUNT']]
			])->distinct('followup')->createCommand()->query();
		$recurringEvents = \App\Modules\Events\Models\RecuringEvents::getInstance();
		while ($row = $dataReader->read()) {
			if (!empty($row['followup'])) {
				$recurringEvents->updateNeverEndingEvents($row['followup']);
			}
		}
	}
}
