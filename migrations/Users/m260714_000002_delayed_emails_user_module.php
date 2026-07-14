<?php
/**
 * FreeCRM - Register DelayedEmails as a user-facing module with recruitment menu access.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260714_000002_delayed_emails_user_module extends Migration
{
	public $transaction = false;

	private const MODULE_NAME = 'DelayedEmails';
	private const MODULE_LABEL = 'Delayed emails';
	private const TABID = 132;
	private const TABID_PROJEKTY = 119;
	private const LIST_URL = 'index.php?module=DelayedEmails&view=ListView';

	public function safeUp(): void
	{
		$this->registerModuleTab();
		$this->registerProfilePermissions();
		$this->insertMenuShortcuts();
		$this->updateSettingsField();
		$this->refreshMenuCache();
		echo "DelayedEmails user module registered.\n";
	}

	public function safeDown(): void
	{
		$tabId = (int) (new Query())
			->select('tabid')
			->from('vtiger_tab')
			->where(['name' => self::MODULE_NAME])
			->scalar($this->db);

		if ($tabId > 0) {
			$this->db->createCommand()->delete('vtiger_profile2tab', ['tabid' => $tabId])->execute();
			$this->db->createCommand()->delete('vtiger_tab_info', ['tabid' => $tabId])->execute();
			$this->db->createCommand()->delete('vtiger_tab', ['tabid' => $tabId])->execute();
		}

		$menuIds = (new Query())
			->select('id')
			->from('yetiforce_menu')
			->where(['like', 'dataurl', '%module=DelayedEmails&view=ListView%'])
			->column($this->db);
		if ($menuIds !== []) {
			$this->db->createCommand()->delete('yetiforce_menu', ['id' => $menuIds])->execute();
		}

		$this->db->createCommand()->update(
			'vtiger_settings_field',
			[
				'linkto' => 'index.php?module=DelayedEmails&parent=Settings&view=ListView',
				'active' => 0,
			],
			['name' => 'LBL_DELAYED_EMAILS']
		)->execute();

		$this->refreshMenuCache();
		echo "DelayedEmails user module rolled back.\n";
	}

	private function registerModuleTab(): void
	{
		if ((new Query())->from('vtiger_tab')->where(['name' => self::MODULE_NAME])->exists($this->db)) {
			return;
		}

		$this->db->createCommand()->insert('vtiger_tab', [
			'tabid' => self::TABID,
			'name' => self::MODULE_NAME,
			'presence' => 0,
			'tabsequence' => 0,
			'tablabel' => self::MODULE_LABEL,
			'modifiedby' => 1,
			'modifiedtime' => date('Y-m-d H:i:s'),
			'customized' => 0,
			'ownedby' => 0,
			'isentitytype' => 0,
			'version' => '1.0',
			'parent' => 'Tools',
			'type' => 0,
		])->execute();

		if (!(new Query())->from('vtiger_tab_info')->where(['tabid' => self::TABID])->exists($this->db)) {
			$this->db->createCommand()->insert('vtiger_tab_info', [
				'tabid' => self::TABID,
				'prefname' => 'isentitytype',
				'prefvalue' => 0,
			])->execute();
		}
	}

	private function registerProfilePermissions(): void
	{
		$tabId = (int) (new Query())
			->select('tabid')
			->from('vtiger_tab')
			->where(['name' => self::MODULE_NAME])
			->scalar($this->db);
		if ($tabId <= 0) {
			throw new \RuntimeException('DelayedEmails tab not registered');
		}

		$allowedProfileIds = (new Query())
			->select('profileid')
			->from('vtiger_profile2tab')
			->where(['tabid' => self::TABID_PROJEKTY, 'permissions' => 0])
			->column($this->db);

		$allProfileIds = (new Query())
			->select('profileid')
			->from('vtiger_profile')
			->column($this->db);

		foreach ($allProfileIds as $profileId) {
			$profileId = (int) $profileId;
			$permissions = in_array($profileId, array_map('intval', $allowedProfileIds), true) ? 0 : 1;
			$exists = (new Query())
				->from('vtiger_profile2tab')
				->where(['profileid' => $profileId, 'tabid' => $tabId])
				->exists($this->db);
			if ($exists) {
				$this->db->createCommand()->update(
					'vtiger_profile2tab',
					['permissions' => $permissions],
					['profileid' => $profileId, 'tabid' => $tabId]
				)->execute();
			} else {
				$this->db->createCommand()->insert('vtiger_profile2tab', [
					'profileid' => $profileId,
					'tabid' => $tabId,
					'permissions' => $permissions,
				])->execute();
			}
		}
	}

	private function insertMenuShortcuts(): void
	{
		if ((new Query())
			->from('yetiforce_menu')
			->where(['like', 'dataurl', '%module=DelayedEmails&view=ListView%'])
			->exists($this->db)) {
			echo "DelayedEmails menu entry already exists — skipping insert.\n";
			return;
		}

		$projectMenus = (new Query())
			->from('yetiforce_menu')
			->where([
				'module' => self::TABID_PROJEKTY,
				'parentid' => 0,
				'type' => 0,
			])
			->orderBy(['role' => SORT_ASC, 'sequence' => SORT_ASC])
			->all($this->db);

		if ($projectMenus === []) {
			throw new \RuntimeException('No ProjektyRekrutacyjne menu entries found in yetiforce_menu');
		}

		$inserted = 0;
		foreach ($projectMenus as $projMenu) {
			$role = (int) $projMenu['role'];

			$dashboard = (new Query())
				->from('yetiforce_menu')
				->where([
					'role' => $role,
					'parentid' => 0,
				])
				->andWhere(['like', 'dataurl', '%module=ProjektyRekrutacyjne&view=DashBoard%'])
				->one($this->db);

			if ($dashboard) {
				$insertSequence = (int) $dashboard['sequence'] + 1;
			} else {
				$insertSequence = (int) $projMenu['sequence'] + 1;
			}

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
				'label' => 'LBL_DELAYED_EMAILS',
				'newwindow' => 0,
				'dataurl' => self::LIST_URL,
				'showicon' => 0,
				'icon' => 'adminIcon-mail-queue',
				'sizeicon' => null,
				'hotkey' => '',
				'filters' => null,
			])->execute();

			$newId = (int) $this->db->getLastInsertID();
			$this->db->createCommand()->update(
				'yetiforce_menu',
				['dataurl' => self::LIST_URL . '&mid=' . $newId . '&parent=0'],
				['id' => $newId]
			)->execute();

			$this->db->createCommand()->update(
				'yetiforce_menu',
				['sequence' => $insertSequence],
				['id' => $newId]
			)->execute();

			++$inserted;
		}

		echo sprintf("Inserted DelayedEmails menu shortcut for %d role(s).\n", $inserted);
	}

	private function updateSettingsField(): void
	{
		$this->db->createCommand()->update(
			'vtiger_settings_field',
			[
				'linkto' => self::LIST_URL,
				'active' => 0,
			],
			['name' => 'LBL_DELAYED_EMAILS']
		)->execute();
	}

	private function refreshMenuCache(): void
	{
		$menuRecordModel = new \App\Modules\Settings\Menu\Models\Record();
		$menuRecordModel->refreshMenuFiles();
	}
}
