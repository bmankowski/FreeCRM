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

namespace App\Modules\API\Cron;

use App\Modules\Cron\Tasks\AbstractCronTask;

final class CalDavTask extends AbstractCronTask
{
	public function execute(): void
	{
		\App\Log\Log::trace('Start cron CalDAV');
		\App\Modules\API\Models\DAV::runCronCalDav();
		\App\Log\Log::trace('End cron CalDAV');
	}
}
