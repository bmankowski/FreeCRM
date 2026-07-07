<?php
/**
 * Backfill role-based picklist infrastructure for fields converted uitype 16→15
 * in m260706_000001..000003 (missing picklist_valueid, vtiger_picklist, role2picklist).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use App\Fields\Picklist;
use yii\db\Migration;
use yii\db\Query;

class m260707_000001_picklist_role_infrastructure extends Migration
{
	private const PICKLIST_FIELDS = [
		'etap_sprzedazy',
		'priorytet',
		'rodzaj',
		'tryb_pracy',
		'zrodlo_pozyskania_projektu',
		'candidate_status',
		'availability',
		'work_time_type',
	];

	public function safeUp(): void
	{
		foreach (self::PICKLIST_FIELDS as $fieldName) {
			$this->bootstrapRoleBasedPicklist($fieldName);
		}
	}

	public function safeDown(): void
	{
		echo "This migration is not reversible automatically.\n";
	}

	private function bootstrapRoleBasedPicklist(string $fieldName): void
	{
		$tableName = "vtiger_{$fieldName}";
		$schema = $this->db->getTableSchema($tableName, true);
		if ($schema === null) {
			echo "Table {$tableName} not found — skipping {$fieldName}.\n";

			return;
		}

		if (!isset($schema->columns['picklist_valueid'])) {
			$this->addColumn(
				$tableName,
				'picklist_valueid',
				$this->integer()->notNull()->defaultValue(0)->after('presence')
			);
			echo "Added picklist_valueid to {$tableName}\n";
		}

		$picklistId = (new Query())
			->select(['picklistid'])
			->from('vtiger_picklist')
			->where(['name' => $fieldName])
			->scalar();
		if ($picklistId === false || $picklistId === null) {
			$this->db->createCommand()->insert('vtiger_picklist', ['name' => $fieldName])->execute();
			$picklistId = (int) $this->db->getLastInsertID('vtiger_picklist_picklistid_seq');
			echo "Registered vtiger_picklist for {$fieldName} (picklistid={$picklistId})\n";
		} else {
			$picklistId = (int) $picklistId;
		}

		$primaryKey = Picklist::getPickListId($fieldName);
		$rows = (new Query())
			->from($tableName)
			->orderBy(['sortorderid' => SORT_ASC, $primaryKey => SORT_ASC])
			->all();

		$roleIds = (new Query())->select('roleid')->from('vtiger_role')->column();
		$inserts = [];

		foreach ($rows as $row) {
			$picklistValueId = (int) ($row['picklist_valueid'] ?? 0);
			if ($picklistValueId <= 0) {
				$picklistValueId = (int) $this->db->getUniqueID('vtiger_picklistvalues');
				$this->update($tableName, ['picklist_valueid' => $picklistValueId], [$primaryKey => $row[$primaryKey]]);
			}

			$sortid = (int) ($row['sortorderid'] ?? 0);
			foreach ($roleIds as $roleId) {
				$exists = (new Query())
					->from('vtiger_role2picklist')
					->where([
						'roleid' => $roleId,
						'picklistvalueid' => $picklistValueId,
						'picklistid' => $picklistId,
					])
					->exists();
				if (!$exists) {
					$inserts[] = [$roleId, $picklistValueId, $picklistId, $sortid];
				}
			}
		}

		if ($inserts !== []) {
			$this->db->createCommand()->batchInsert(
				'vtiger_role2picklist',
				['roleid', 'picklistvalueid', 'picklistid', 'sortid'],
				$inserts
			)->execute();
			echo "Inserted " . count($inserts) . " role2picklist rows for {$fieldName}\n";
		}

		\App\Cache\Cache::delete('getPickListValues', $fieldName);
		foreach ($roleIds as $roleId) {
			\App\Cache\Cache::delete('getRoleBasedPicklistValues', $fieldName . $roleId);
		}
	}
}
