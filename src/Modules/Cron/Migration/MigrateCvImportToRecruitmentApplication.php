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

namespace App\Modules\Cron\Migration;

use App\Modules\RecruitmentApplication\Cron\CvImportTask;

final class MigrateCvImportToRecruitmentApplication
{
	public static function execute(): void
	{
		$db = \App\Db\Db::getInstance();
		$transaction = $db->beginTransaction();
		try {
			$handlerClass = CvImportTask::class;
			$exists = (new \App\Db\Query())
				->from('vtiger_cron_task')
				->where(['handler_class' => $handlerClass])
				->exists();
			if (!$exists) {
				\vtlib\Cron::registerClassTask(
					'LBL_SCHEDULED_CV_IMPORT',
					$handlerClass,
					300,
					'RecruitmentApplication',
					\vtlib\Cron::STATUS_ENABLED,
					0,
					'Import CV applications from import/cv/pending'
				);
			}

			$deleted = (int) $db->createCommand()
				->delete('vtiger_cron_task', [
					'or',
					['handler_class' => 'App\\Modules\\Kandydaci\\Cron\\ScheduledImportTask'],
					['module' => 'Kandydaci', 'name' => 'LBL_SCHEDULED_IMPORT'],
				])
				->execute();

			$transaction->commit();
			echo sprintf("Registered RecruitmentApplication CV import; removed %d legacy scheduled-import cron row(s).\n", $deleted);
		} catch (\Throwable $e) {
			$transaction->rollBack();
			\App\Log\Log::error($e, 'CRON');
			throw $e;
		}
	}
}
