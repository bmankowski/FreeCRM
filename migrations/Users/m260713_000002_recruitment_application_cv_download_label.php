<?php
/**
 * RecruitmentApplication: no-op (cv_original_filename stays visible; download label uses Field.php).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260713_000002_recruitment_application_cv_download_label extends Migration
{
	public $transaction = false;

	public function safeUp(): void
	{
		$this->clearFieldCache();
	}

	public function safeDown(): void
	{
		$this->clearFieldCache();
	}

	private function clearFieldCache(): void
	{
		\App\Cache\Cache::delete('ModuleFields', (string) self::TABID);
		\App\Cache\Cache::delete('fieldInfo', (string) self::TABID);
	}
}
