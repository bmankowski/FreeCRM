<?php
/**
 * FreeCRM - Fix vtiger_fieldmodulerel rows still referencing Kandydaci after module rename.
 *
 * Field::getRelatedFieldForModule() joins relmodule; stale values break direct relations
 * (e.g. RecruitmentApplication candidate_id → Candidates related list).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260609_000007_fix_fieldmodulerel_kandydaci extends Migration
{
	public function safeUp(): void
	{
		$moduleCount = $this->db->createCommand(
			"UPDATE vtiger_fieldmodulerel SET module = 'Candidates' WHERE module = 'Kandydaci'"
		)->execute();
		echo "Updated $moduleCount vtiger_fieldmodulerel module row(s)\n";

		$relmoduleCount = $this->db->createCommand(
			"UPDATE vtiger_fieldmodulerel SET relmodule = 'Candidates' WHERE relmodule = 'Kandydaci'"
		)->execute();
		echo "Updated $relmoduleCount vtiger_fieldmodulerel relmodule row(s)\n";

		\App\Cache\Cache::delete('getRelatedFieldForModule', 'all');
		echo "Cleared getRelatedFieldForModule cache\n";
	}

	public function safeDown(): void
	{
		echo "This migration is not reversible automatically.\n";
	}
}
