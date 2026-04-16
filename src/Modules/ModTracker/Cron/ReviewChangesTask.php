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

namespace App\Modules\ModTracker\Cron;

use App\Modules\Cron\Tasks\AbstractCronTask;

final class ReviewChangesTask extends AbstractCronTask
{
	public function execute(): void
	{
		$db = \App\Db\Db::getInstance();
		$query = (new \App\Db\Query())->from('u_#__reviewed_queue');
		$dataReader = $query->createCommand($db)->query();
		$reviewed = new CronReviewed();
		while ($row = $dataReader->read()) {
			$reviewed->clearData();
			$reviewed->init($row);
			$reviewed->reviewChanges();
			if ($reviewed->isEnd()) {
				break;
			}
		}
	}
}
