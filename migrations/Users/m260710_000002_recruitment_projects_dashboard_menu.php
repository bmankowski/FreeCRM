<?php
/**
 * FreeCRM - Add recruitment projects dashboard link to main menu (yetiforce_menu).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260710_000002_recruitment_projects_dashboard_menu extends Migration
{
	public $transaction = false;

	private const TABID_PROJEKTY = 119;

	private const DASHBOARD_BASE_URL = 'index.php?module=ProjektyRekrutacyjne&view=DashBoard';

	public function safeUp(): void
	{
		$exists = (new \App\Db\Query())
			->from('yetiforce_menu')
			->where(['like', 'dataurl', '%module=ProjektyRekrutacyjne&view=DashBoard%'])
			->exists();
		if ($exists) {
			echo "Recruitment dashboard menu entry already exists — skipping insert.\n";
			$this->refreshMenuCache();
			return;
		}

		$projectMenus = (new \App\Db\Query())
			->from('yetiforce_menu')
			->where([
				'module' => self::TABID_PROJEKTY,
				'parentid' => 0,
				'type' => 0,
			])
			->orderBy(['role' => SORT_ASC, 'sequence' => SORT_ASC])
			->all();

		if ($projectMenus === []) {
			throw new \RuntimeException('No ProjektyRekrutacyjne menu entries found in yetiforce_menu');
		}

		$inserted = 0;
		foreach ($projectMenus as $projMenu) {
			$role = (int) $projMenu['role'];
			$insertSequence = (int) $projMenu['sequence'] + 1;

			$this->db->createCommand(
				'UPDATE yetiforce_menu SET sequence = sequence + 1
				 WHERE role = :role AND parentid = 0 AND sequence >= :seq',
				[':role' => $role, ':seq' => $insertSequence]
			)->execute();

			$this->db->createCommand()->insert('yetiforce_menu', [
				'role' => $role,
				'parentid' => 0,
				'type' => 1,
				'module' => null,
				'label' => 'LBL_RECRUITMENT_PROJECTS_DASHBOARD',
				'newwindow' => 0,
				'dataurl' => self::DASHBOARD_BASE_URL,
				'showicon' => 0,
				'icon' => 'userIcon-ProjektyRekrutacyjne',
				'sizeicon' => null,
				'hotkey' => '',
				'filters' => null,
			])->execute();

			$newId = (int) $this->db->getLastInsertID();
			$this->db->createCommand()->update(
				'yetiforce_menu',
				['dataurl' => self::DASHBOARD_BASE_URL . '&mid=' . $newId . '&parent=0'],
				['id' => $newId]
			)->execute();

			$this->db->createCommand()->update(
				'yetiforce_menu',
				['sequence' => $insertSequence],
				['id' => $newId]
			)->execute();

			++$inserted;
		}

		echo sprintf("Inserted recruitment dashboard menu shortcut for %d role(s).\n", $inserted);
		$this->refreshMenuCache();
	}

	public function safeDown(): void
	{
		$ids = (new \App\Db\Query())
			->select('id')
			->from('yetiforce_menu')
			->where(['like', 'dataurl', '%module=ProjektyRekrutacyjne&view=DashBoard%'])
			->column();

		if ($ids === []) {
			echo "No recruitment dashboard menu entries to remove.\n";
			return;
		}

		$this->db->createCommand()->delete('yetiforce_menu', ['id' => $ids])->execute();
		echo sprintf("Removed %d recruitment dashboard menu entry(ies).\n", count($ids));
		$this->refreshMenuCache();
	}

	private function refreshMenuCache(): void
	{
		$menuRecordModel = new \App\Modules\Settings\Menu\Models\Record();
		$menuRecordModel->refreshMenuFiles();
	}
}
