<?php
/**
 * FreeCRM - Post-rename fixes after Kandydaci → Candidates (email templates, related list labels).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260609_000002_fix_kandydaci_module_references extends Migration
{
	public function safeUp(): void
	{
		$emailCount = $this->db->createCommand(
			"UPDATE u_yf_emailtemplates SET module = 'Candidates' WHERE module = 'Kandydaci'"
		)->execute();
		echo "Updated $emailCount u_yf_emailtemplates rows\n";

		$relatedCount = $this->db->createCommand(
			"UPDATE vtiger_relatedlists SET label = 'Candidates' WHERE label = 'Kandydaci'"
		)->execute();
		echo "Updated $relatedCount vtiger_relatedlists label rows\n";

		\vtlib\Deprecated::createModuleMetaFile();
		echo "Regenerated user_privileges/tabdata.php\n";

		$menuRecordModel = new \App\Modules\Settings\Menu\Models\Record();
		$menuRecordModel->refreshMenuFiles();
		$menuFiles = glob(ROOT_DIRECTORY . '/user_privileges/menu_*.php') ?: [];
		echo sprintf("Regenerated %d menu privilege file(s)\n", count($menuFiles));
	}

	public function safeDown(): void
	{
		echo "This migration is not reversible automatically.\n";
	}
}
