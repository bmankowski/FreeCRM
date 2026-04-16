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

namespace App\Modules\Kandydaci\Cron;

use App\Modules\Cron\Tasks\AbstractCronTask;
use App\Modules\Kandydaci\Crons\ScheduledImport;

final class ScheduledImportTask extends AbstractCronTask
{
	public function execute(): void
	{
		ScheduledImport::importNewCandidates();
	}
}

