<?php
/**
 * Add field_kind and storage_type columns to vtiger_field and backfill from legacy metadata.
 * Runtime still uses uitype/typeofdata — column wiring is a follow-up CR.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use App\Field\FieldKind;
use App\Field\StorageType;
use yii\db\Migration;

class m260705_000001_vtiger_field_kind_and_storage_type extends Migration
{
	public function safeUp(): void
	{
		$schema = $this->db->getSchema()->getTableSchema('vtiger_field', true);

		if ($schema !== null && !isset($schema->columns['field_kind'])) {
			$this->addColumn(
				'vtiger_field',
				'field_kind',
				$this->string(50)->notNull()->defaultValue('')->after('uitype')
			);
			$this->createIndex('field_kind_idx', 'vtiger_field', 'field_kind');
		}

		if ($schema !== null && !isset($schema->columns['storage_type'])) {
			$this->addColumn(
				'vtiger_field',
				'storage_type',
				$this->string(20)->notNull()->defaultValue('')->after('typeofdata')
			);
			$this->createIndex('field_storage_type_idx', 'vtiger_field', 'storage_type');
		}

		$webserviceByUitype = [];
		foreach ((new \App\Db\Query())->select(['uitype', 'fieldtype'])->from('vtiger_ws_fieldtype')->all() as $wsRow) {
			$webserviceByUitype[(int) $wsRow['uitype']] = (string) $wsRow['fieldtype'];
		}

		$rows = (new \App\Db\Query())
			->select(['fieldid', 'uitype', 'fieldname', 'typeofdata', 'field_kind', 'storage_type'])
			->from('vtiger_field')
			->all();

		foreach ($rows as $row) {
			$fieldId = (int) $row['fieldid'];
			$updates = [];

			if (($row['field_kind'] ?? '') === '') {
				$updates['field_kind'] = FieldKind::resolve(
					(int) $row['uitype'],
					(string) $row['fieldname'],
					(string) ($row['typeofdata'] ?? 'V'),
					$webserviceByUitype
				);
			}

			if (($row['storage_type'] ?? '') === '') {
				$updates['storage_type'] = StorageType::fromTypeofdata((string) ($row['typeofdata'] ?? 'V'));
			}

			if ($updates !== []) {
				$this->update('vtiger_field', $updates, ['fieldid' => $fieldId]);
			}
		}

		$emptyKind = (int) $this->db->createCommand(
			"SELECT COUNT(*) FROM vtiger_field WHERE field_kind = ''"
		)->queryScalar();
		if ($emptyKind !== 0) {
			throw new \RuntimeException(
				"m260705_000001: expected 0 empty field_kind rows after backfill, found {$emptyKind}"
			);
		}

		$emptyStorage = (int) $this->db->createCommand(
			"SELECT COUNT(*) FROM vtiger_field WHERE storage_type = ''"
		)->queryScalar();
		if ($emptyStorage !== 0) {
			throw new \RuntimeException(
				"m260705_000001: expected 0 empty storage_type rows after backfill, found {$emptyStorage}"
			);
		}
	}

	public function safeDown(): void
	{
		echo "m260705_000001: safeDown not supported — restore DB backup.\n";
	}
}
