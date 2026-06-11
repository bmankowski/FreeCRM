<?php
/**
 * FreeCRM - Fix Candidates menu icon after Kandydaci → Candidates rename.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260611_000001_fix_candidates_menu_icon extends Migration
{
	public $transaction = false;

	private const TABID = 121;

	public function safeUp(): void
	{
		$updated = $this->db->createCommand(
			"UPDATE yetiforce_menu SET icon = 'userIcon-Candidates'
			 WHERE icon = 'userIcon-Kandydaci' AND module = :tabid",
			[':tabid' => self::TABID]
		)->execute();

		echo sprintf("Updated %d menu icon(s) from userIcon-Kandydaci to userIcon-Candidates.\n", $updated);

		$menuRecordModel = new \App\Modules\Settings\Menu\Models\Record();
		$menuRecordModel->refreshMenuFiles();
	}

	public function safeDown(): void
	{
		$this->db->createCommand(
			"UPDATE yetiforce_menu SET icon = 'userIcon-Kandydaci'
			 WHERE icon = 'userIcon-Candidates' AND module = :tabid",
			[':tabid' => self::TABID]
		)->execute();

		$menuRecordModel = new \App\Modules\Settings\Menu\Models\Record();
		$menuRecordModel->refreshMenuFiles();
	}
}
