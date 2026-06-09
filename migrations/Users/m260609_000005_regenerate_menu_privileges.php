<?php
/**
 * FreeCRM - Regenerate user_privileges/menu_*.php after cache wipe (post Kandydaci migration).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260609_000005_regenerate_menu_privileges extends Migration
{
	public function safeUp(): void
	{
		$menuRecordModel = new \App\Modules\Settings\Menu\Models\Record();
		$menuRecordModel->refreshMenuFiles();

		$files = glob(ROOT_DIRECTORY . '/user_privileges/menu_*.php') ?: [];
		echo sprintf("Regenerated %d menu privilege file(s).\n", count($files));
		foreach ($files as $file) {
			echo '  ' . basename($file) . "\n";
		}

		if (!is_file(ROOT_DIRECTORY . '/user_privileges/menu_0.php')) {
			throw new \RuntimeException('menu_0.php was not created');
		}
	}

	public function safeDown(): void
	{
		echo "This migration is not reversible automatically.\n";
	}
}
