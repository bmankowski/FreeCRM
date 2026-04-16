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

namespace App\Modules\Reports\Cron;

use App\Modules\Cron\Tasks\AbstractCronTask;

final class ScheduleReportsTask extends AbstractCronTask
{
	public function execute(): void
	{
		\App\Modules\Reports\Models\ScheduleReports::runScheduledReports();
	}
}
