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

use App\Modules\RecruitmentApplication\Cron\CvImportCandidatesTask;
use App\Modules\RecruitmentApplication\Cron\CvImportTask;

final class MigrateCvImportSplitApplicationsAndCandidates
{
	public static function execute(): void
	{
		$db = \App\Db\Db::getInstance();
		$transaction = $db->beginTransaction();
		try {
			$db->createCommand()
				->update(
					'vtiger_cron_task',
					['name' => 'LBL_SCHEDULED_CV_IMPORT_APPLICATIONS'],
					['name' => 'LBL_SCHEDULED_CV_IMPORT']
				)
				->execute();

			$candidatesHandler = CvImportCandidatesTask::class;
			$exists = (new \App\Db\Query())
				->from('vtiger_cron_task')
				->where(['handler_class' => $candidatesHandler])
				->exists();
			if (!$exists) {
				\vtlib\Cron::registerClassTask(
					'LBL_SCHEDULED_CV_IMPORT_CANDIDATES',
					$candidatesHandler,
					60,
					'RecruitmentApplication',
					\vtlib\Cron::STATUS_ENABLED,
					0,
					'Materialize candidates for imported CV applications'
				);
			}

			$db->createCommand()
				->update(
					'vtiger_cron_task',
					['description' => 'Import CV applications from import/cv/pending'],
					['handler_class' => CvImportTask::class]
				)
				->execute();

			$transaction->commit();
			echo "CV import cron split: applications + candidates tasks registered.\n";
		} catch (\Throwable $e) {
			$transaction->rollBack();
			\App\Log\Log::error($e, 'CRON');
			throw $e;
		}
	}
}
