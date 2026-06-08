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

namespace App\Modules\LinkAction\Cron;

use App\Modules\Cron\Tasks\AbstractCronTask;
use App\Modules\LinkAction\Services\QueueImporter;
use App\Modules\LinkAction\Services\QueuePuller;
use App\Modules\Users\Models\Record as UsersRecord;

final class ImportTask extends AbstractCronTask
{
	public function execute(): void
	{
		$automatId = UsersRecord::getUserIdByName('automat');
		UsersRecord::setCurrentUserId($automatId);
		$puller = new QueuePuller();
		$puller->fetch();
		(new QueueImporter())->importIncoming();
		$puller->ack();
	}
}
