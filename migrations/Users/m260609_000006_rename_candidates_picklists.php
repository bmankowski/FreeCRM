<?php
/**
 * FreeCRM - Rename Candidates picklist tables to match renamed field names.
 *
 * Picklist::getPickListValues() resolves tables as vtiger_{fieldname}; field renames in
 * m260609_000001 left legacy Polish table names (status_kandydata, dostepnosc, etc.).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260609_000006_rename_candidates_picklists extends Migration
{
	/** @var array<string, array{table_old: string, table_new: string, id_old: string, id_new: string, value_old: string, value_new: string, picklist_name_old: string|null, picklist_name_new: string}> */
	private const PICKLISTS = [
		'candidate_status' => [
			'table_old' => 'vtiger_status_kandydata',
			'table_new' => 'vtiger_candidate_status',
			'id_old' => 'status_kandydataid',
			'id_new' => 'candidate_statusid',
			'value_old' => 'status_kandydata',
			'value_new' => 'candidate_status',
			'picklist_name_old' => 'status_kandydata',
			'picklist_name_new' => 'candidate_status',
		],
		'availability' => [
			'table_old' => 'vtiger_dostepnosc',
			'table_new' => 'vtiger_availability',
			'id_old' => 'dostepnoscid',
			'id_new' => 'availabilityid',
			'value_old' => 'dostepnosc',
			'value_new' => 'availability',
			'picklist_name_old' => 'dostepnosc',
			'picklist_name_new' => 'availability',
		],
		'work_time_type' => [
			'table_old' => 'vtiger_wymiar_czasu_pracy',
			'table_new' => 'vtiger_work_time_type',
			'id_old' => 'wymiar_czasu_pracyid',
			'id_new' => 'work_time_typeid',
			'value_old' => 'wymiar_czasu_pracy',
			'value_new' => 'work_time_type',
			'picklist_name_old' => 'wymiar_czasu_pracy',
			'picklist_name_new' => 'work_time_type',
		],
	];

	public function safeUp(): void
	{
		foreach (self::PICKLISTS as $field => $spec) {
			$this->renamePicklist($field, $spec);
		}
	}

	public function safeDown(): void
	{
		echo "This migration is not reversible automatically.\n";
	}

	/**
	 * @param array{table_old: string, table_new: string, id_old: string, id_new: string, value_old: string, value_new: string, picklist_name_old: string|null, picklist_name_new: string} $spec
	 */
	private function renamePicklist(string $field, array $spec): void
	{
		if ($this->db->getTableSchema($spec['table_new'], true) !== null) {
			echo "Picklist table {$spec['table_new']} already exists — skipping $field.\n";
			return;
		}
		if ($this->db->getTableSchema($spec['table_old'], true) === null) {
			echo "Legacy picklist table {$spec['table_old']} not found — skipping $field.\n";
			return;
		}

		$this->renameTable($spec['table_old'], $spec['table_new']);
		echo "Renamed table {$spec['table_old']} → {$spec['table_new']}\n";

		$schema = $this->db->getTableSchema($spec['table_new'], true);
		if ($schema !== null && isset($schema->columns[$spec['id_old']]) && !isset($schema->columns[$spec['id_new']])) {
			$this->renameColumn($spec['table_new'], $spec['id_old'], $spec['id_new']);
		}
		if ($schema !== null && isset($schema->columns[$spec['value_old']]) && !isset($schema->columns[$spec['value_new']])) {
			$this->renameColumn($spec['table_new'], $spec['value_old'], $spec['value_new']);
		}
		echo "Renamed picklist columns for $field\n";

		if ($spec['picklist_name_old'] !== null) {
			$count = $this->db->createCommand(
				'UPDATE vtiger_picklist SET name = :new WHERE name = :old',
				[':new' => $spec['picklist_name_new'], ':old' => $spec['picklist_name_old']]
			)->execute();
			if ($count === 0 && $this->db->createCommand(
				'SELECT COUNT(*) FROM vtiger_picklist WHERE name = :name',
				[':name' => $spec['picklist_name_new']]
			)->queryScalar() == 0) {
				$this->db->createCommand()->insert('vtiger_picklist', [
					'name' => $spec['picklist_name_new'],
				])->execute();
				echo "Inserted vtiger_picklist row for {$spec['picklist_name_new']}\n";
			} else {
				echo "Updated $count vtiger_picklist row(s) for $field\n";
			}
		}
	}
}
