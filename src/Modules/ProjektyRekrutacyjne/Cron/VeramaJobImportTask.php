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

namespace App\Modules\ProjektyRekrutacyjne\Cron;

use App\Modules\Cron\Tasks\AbstractCronTask;
use App\Modules\ProjektyRekrutacyjne\Services\Verama\VeramaJobImporter;
use App\Modules\Users\Models\Record as UsersRecord;

final class VeramaJobImportTask extends AbstractCronTask
{
	public function execute(): void
	{
		$automatId = UsersRecord::getUserIdByName('automat');
		UsersRecord::setCurrentUserId($automatId);
		(new VeramaJobImporter())->importFromPending();
	}
}
