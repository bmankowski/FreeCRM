<?php
/**
 * FreeCRM - Set all users to pl_pl and remove unused de_de language pack.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260616_000001_remove_de_de_language extends Migration
{
	public $transaction = false;

	public function safeUp(): void
	{
		$updated = $this->db->createCommand(
			"UPDATE vtiger_users SET language = 'pl_pl' WHERE language IS NULL OR language = '' OR language = 'None' OR language <> 'pl_pl'"
		)->execute();
		echo sprintf("Set language=pl_pl on %d user(s).\n", $updated);

		$deleted = $this->db->createCommand(
			"DELETE FROM vtiger_language WHERE prefix = 'de_de'"
		)->execute();
		echo sprintf("Removed %d vtiger_language row(s) for de_de.\n", $deleted);

		\App\Utils\VtlibUtils::recreateUserPrivilegeFiles();
		echo "Regenerated user_privileges_*.php from DB.\n";
	}

	public function safeDown(): void
	{
		echo "This migration is not reversible automatically.\n";
	}
}
