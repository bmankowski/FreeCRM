<?php
/**
 * FreeCRM - Lower CV import cron frequency to 60s (MINIMUM_CRON_FREQUENCY).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260721_000001_cv_import_cron_frequency_60 extends Migration
{
	private const FREQUENCY_SECONDS = 60;
	private const PREVIOUS_FREQUENCY_SECONDS = 300;

	/** @var list<string> */
	private const TASK_NAMES = [
		'LBL_SCHEDULED_CV_IMPORT_APPLICATIONS',
		'LBL_SCHEDULED_CV_IMPORT_CANDIDATES',
	];

	public function safeUp(): void
	{
		$this->update(
			'vtiger_cron_task',
			['frequency' => self::FREQUENCY_SECONDS],
			['name' => self::TASK_NAMES]
		);
	}

	public function safeDown(): void
	{
		$this->update(
			'vtiger_cron_task',
			['frequency' => self::PREVIOUS_FREQUENCY_SECONDS],
			['name' => self::TASK_NAMES]
		);
	}
}
