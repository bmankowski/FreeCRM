<?php
/**
 * FreeCRM - Rename Kandydaci module to Candidates (tables, columns, vtiger metadata).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260609_000001_rename_kandydaci_to_candidates extends Migration
{
	private const TABID = 121;
	private const TABLE_OLD = 'u_yf_kandydaci';
	private const TABLE_NEW = 'u_yf_candidates';
	private const TABLE_CF_OLD = 'u_yf_kandydacicf';
	private const TABLE_CF_NEW = 'u_yf_candidatescf';

	/** @var array<string, string> old => new column names on u_yf_candidates */
	private const MAIN_COLUMN_MAP = [
		'kandydaciid' => 'candidatesid',
		'status_kandydata' => 'candidate_status',
		'telefon_extra' => 'phone_extra',
		'telefon' => 'phone',
		'rekrutowany_stanowisko' => 'recruited_position',
		'dostepnosc' => 'availability',
		'wymiar_czasu_pracy' => 'work_time_type',
		'polec_znajomego' => 'referrer_consultant_id',
	];

	/** @var array<string, string> */
	private const CF_COLUMN_MAP = [
		'kandydaciid' => 'candidatesid',
		'ilosc_dokumentow_kandydata' => 'documents_count',
		'ilosc_dokumentow' => 'documents_count_legacy',
		'projekt_na_ktory_ostatnio_wysl' => 'last_sent_to_project_id',
		'data_maksymalny_kontakt_rodo' => 'gdpr_max_contact_date',
		'oczekiwania_finansowe_brutto' => 'salary_expectation_gross',
		'data_ostatniego_wyslania' => 'last_sent_to_project_date',
		'email_prywatny' => 'email_private',
		'email_firmowy' => 'email_business',
		'zrodlo_aplikacji' => 'application_source',
		'tresc_cv' => 'cv_text',
		'komunikator' => 'messenger',
	];

	public function safeUp(): void
	{
		if ($this->isAlreadyMigrated()) {
			echo "Kandydaci → Candidates migration already applied.\n";
			return;
		}

		$transaction = $this->db->beginTransaction();
		try {
			$this->renameDataTables();
			$this->renameMainColumns();
			$this->renameCfColumns();
			$this->updateVtigerTab();
			$this->updateVtigerFieldTableNames();
			$this->updateVtigerFieldNames();
			$this->updateVtigerEntityName();
			$this->updateCustomViews();
			$this->updateCrmentitySetype();
			$this->updateVtigerLinks();
			$this->updateCustomViewColumnStrings();
			$this->updateRecruitmentApplicationField();
			$this->updateLinkActionLog();
			$this->updateTemplateElements();
			$transaction->commit();
			echo "Kandydaci → Candidates migration completed.\n";
		} catch (\Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	public function safeDown(): void
	{
		echo "This migration is not reversible automatically — restore from tmp/db-backups/.\n";
	}

	private function isAlreadyMigrated(): bool
	{
		$tab = (new \yii\db\Query())
			->select(['name'])
			->from('vtiger_tab')
			->where(['tabid' => self::TABID])
			->scalar();
		return $tab === 'Candidates';
	}

	private function renameDataTables(): void
	{
		if ($this->db->getTableSchema(self::TABLE_OLD, true) !== null) {
			$this->renameTable(self::TABLE_OLD, self::TABLE_NEW);
			echo "Renamed table " . self::TABLE_OLD . " → " . self::TABLE_NEW . "\n";
		}
		if ($this->db->getTableSchema(self::TABLE_CF_OLD, true) !== null) {
			$this->renameTable(self::TABLE_CF_OLD, self::TABLE_CF_NEW);
			echo "Renamed table " . self::TABLE_CF_OLD . " → " . self::TABLE_CF_NEW . "\n";
		}
	}

	private function renameMainColumns(): void
	{
		foreach (self::MAIN_COLUMN_MAP as $old => $new) {
			if ($old === $new) {
				continue;
			}
			$schema = $this->db->getTableSchema(self::TABLE_NEW, true);
			if ($schema !== null && isset($schema->columns[$old]) && !isset($schema->columns[$new])) {
				$this->renameColumn(self::TABLE_NEW, $old, $new);
				echo "Renamed column " . self::TABLE_NEW . ".$old → $new\n";
			}
		}
	}

	private function renameCfColumns(): void
	{
		foreach (self::CF_COLUMN_MAP as $old => $new) {
			if ($old === $new) {
				continue;
			}
			$schema = $this->db->getTableSchema(self::TABLE_CF_NEW, true);
			if ($schema !== null && isset($schema->columns[$old]) && !isset($schema->columns[$new])) {
				$this->renameColumn(self::TABLE_CF_NEW, $old, $new);
				echo "Renamed column " . self::TABLE_CF_NEW . ".$old → $new\n";
			}
		}
	}

	private function updateVtigerTab(): void
	{
		$this->db->createCommand()->update('vtiger_tab', [
			'name' => 'Candidates',
			'tablabel' => 'Candidates',
		], ['tabid' => self::TABID])->execute();
		echo "Updated vtiger_tab name/tablabel to Candidates\n";
	}

	private function updateVtigerFieldTableNames(): void
	{
		$this->db->createCommand(
			"UPDATE vtiger_field SET tablename = :new WHERE tablename = :old",
			[':new' => self::TABLE_NEW, ':old' => self::TABLE_OLD]
		)->execute();
		$this->db->createCommand(
			"UPDATE vtiger_field SET tablename = :new WHERE tablename = :old",
			[':new' => self::TABLE_CF_NEW, ':old' => self::TABLE_CF_OLD]
		)->execute();
		echo "Updated vtiger_field tablename references\n";
	}

	private function updateVtigerFieldNames(): void
	{
		$allMaps = array_merge(self::MAIN_COLUMN_MAP, self::CF_COLUMN_MAP);
		foreach ($allMaps as $old => $new) {
			if ($old === $new) {
				continue;
			}
			$this->db->createCommand()->update('vtiger_field', [
				'fieldname' => $new,
				'columnname' => $new,
			], ['tabid' => self::TABID, 'fieldname' => $old])->execute();
		}
		echo "Updated vtiger_field fieldname/columnname for renamed columns\n";
	}

	private function updateVtigerEntityName(): void
	{
		$this->db->createCommand()->update('vtiger_entityname', [
			'modulename' => 'Candidates',
			'tablename' => self::TABLE_NEW,
			'entityidfield' => 'candidatesid',
			'entityidcolumn' => 'candidatesid',
			'searchcolumn' => 'name,recruited_position,phone,phone_extra,application_id',
		], ['modulename' => 'Kandydaci'])->execute();
		echo "Updated vtiger_entityname\n";
	}

	private function updateCustomViews(): void
	{
		$count = $this->db->createCommand(
			"UPDATE vtiger_customview SET entitytype = 'Candidates' WHERE entitytype = 'Kandydaci'"
		)->execute();
		echo "Updated $count vtiger_customview rows\n";
	}

	private function updateCrmentitySetype(): void
	{
		$count = $this->db->createCommand(
			"UPDATE vtiger_crmentity SET setype = 'Candidates' WHERE setype = 'Kandydaci'"
		)->execute();
		echo "Updated $count vtiger_crmentity setype rows\n";
	}

	private function updateVtigerLinks(): void
	{
		$count = $this->db->createCommand(
			"UPDATE vtiger_links SET linkurl = REPLACE(linkurl, 'module=Kandydaci', 'module=Candidates') WHERE tabid = :tabid",
			[':tabid' => self::TABID]
		)->execute();
		echo "Updated $count vtiger_links rows\n";
	}

	private function updateCustomViewColumnStrings(): void
	{
		$replacements = $this->buildColumnStringReplacements();
		foreach (['vtiger_cvcolumnlist', 'vtiger_cvadvfilter'] as $table) {
			foreach ($replacements as $old => $new) {
				$this->db->createCommand(
					"UPDATE $table SET columnname = REPLACE(columnname, :old, :new)",
					[':old' => $old, ':new' => $new]
				)->execute();
			}
		}
		echo "Updated custom view column strings in vtiger_cvcolumnlist and vtiger_cvadvfilter\n";
	}

	/** @return array<string, string> sorted longest-first */
	private function buildColumnStringReplacements(): array
	{
		$replacements = [
			self::TABLE_OLD => self::TABLE_NEW,
			self::TABLE_CF_OLD => self::TABLE_CF_NEW,
			'Kandydaci_' => 'Candidates_',
		];
		foreach (array_merge(self::MAIN_COLUMN_MAP, self::CF_COLUMN_MAP) as $old => $new) {
			if ($old !== $new) {
				$replacements[":$old:$old:"] = ":$new:$new:";
			}
		}
		uksort($replacements, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
		return $replacements;
	}

	private function updateRecruitmentApplicationField(): void
	{
		if ($this->db->getTableSchema('vtiger_recruitmentapplicationcf', true) === null) {
			return;
		}
		$schema = $this->db->getTableSchema('vtiger_recruitmentapplicationcf', true);
		if ($schema !== null && isset($schema->columns['kandydaci_id']) && !isset($schema->columns['candidate_id'])) {
			$this->renameColumn('vtiger_recruitmentapplicationcf', 'kandydaci_id', 'candidate_id');
		}
		$this->db->createCommand()->update('vtiger_field', [
			'fieldname' => 'candidate_id',
			'columnname' => 'candidate_id',
		], ['fieldname' => 'kandydaci_id'])->execute();
		echo "Renamed RecruitmentApplication kandydaci_id → candidate_id\n";
	}

	private function updateLinkActionLog(): void
	{
		if ($this->db->getTableSchema('u_yf_link_action_log', true) === null) {
			return;
		}
		$count = $this->db->createCommand(
			"UPDATE u_yf_link_action_log SET module = 'Candidates' WHERE module = 'Kandydaci'"
		)->execute();
		echo "Updated $count u_yf_link_action_log rows\n";
	}

	private function updateTemplateElements(): void
	{
		if ($this->db->getTableSchema('u_yf_templateelements', true) === null) {
			return;
		}
		$this->db->createCommand(
			"UPDATE u_yf_templateelements SET module_name = 'Candidates' WHERE module_name = 'Kandydaci'"
		)->execute();
		$this->db->createCommand(
			"UPDATE u_yf_templateelements SET content = REPLACE(content, 'email_prywatny', 'email_private') WHERE code IN ('kandydaci_unsubscribe_footer', 'kandydaci_open_tracking_logo')"
		)->execute();
		echo "Updated template elements module_name and email field tokens\n";
	}
}
