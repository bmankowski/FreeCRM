<?php
/**
 * FreeCRM - Rename Kandydaci storage paths on disk + DB JSON paths + LinkAction template element codes.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260609_000004_rename_kandydaci_storage_and_template_codes extends Migration
{
	private const ELEMENT_CODE_MAP = [
		'kandydaci_unsubscribe_footer' => 'candidates_unsubscribe_footer',
		'kandydaci_open_tracking_logo' => 'candidates_open_tracking_logo',
	];

	private const LABEL_MAP = [
		'LBL_KANDYDACI_UNSUBSCRIBE_FOOTER' => 'LBL_CANDIDATES_UNSUBSCRIBE_FOOTER',
		'LBL_KANDYDACI_OPEN_TRACKING_LOGO' => 'LBL_CANDIDATES_OPEN_TRACKING_LOGO',
	];

	public function safeUp(): void
	{
		require_once ROOT_DIRECTORY . '/bin/migrate_candidates_storage_paths.php';
		foreach (migrateCandidatesStoragePaths(false) as $line) {
			echo $line . "\n";
		}

		$this->renameCvImagePaths();
		$this->renameTemplateElementCodes();
		$this->renameRecruitmentApplicationFieldLabel();
	}

	public function safeDown(): void
	{
		echo "This migration is not reversible automatically.\n";
	}

	private function renameCvImagePaths(): void
	{
		$count = (int) $this->db->createCommand(
			"UPDATE u_yf_candidates SET cv_img_file = REPLACE(cv_img_file, 'Kandydaci', 'Candidates') WHERE cv_img_file LIKE '%Kandydaci%'"
		)->execute();
		echo "Updated cv_img_file paths on $count candidate row(s)\n";
	}

	private function renameTemplateElementCodes(): void
	{
		foreach (self::ELEMENT_CODE_MAP as $oldCode => $newCode) {
			$updated = (int) $this->db->createCommand()->update(
				'u_yf_templateelements',
				['code' => $newCode],
				['code' => $oldCode]
			)->execute();
			echo "Renamed template element code $oldCode → $newCode ($updated row(s))\n";

			$entityUpdated = (int) $this->db->createCommand()->update(
				'vtiger_crmentity',
				['description' => $newCode],
				['setype' => 'TemplateElements', 'description' => $oldCode]
			)->execute();
			echo "Updated $entityUpdated vtiger_crmentity description(s) for $newCode\n";
		}

		foreach (self::LABEL_MAP as $oldLabel => $newLabel) {
			$updated = (int) $this->db->createCommand()->update(
				'u_yf_templateelements',
				['label' => $newLabel],
				['label' => $oldLabel]
			)->execute();
			echo "Renamed template element label $oldLabel → $newLabel ($updated row(s))\n";
		}

		foreach (self::ELEMENT_CODE_MAP as $oldCode => $newCode) {
			$templates = (int) $this->db->createCommand(
				"UPDATE u_yf_emailtemplates SET content = REPLACE(content, :old, :new) WHERE content LIKE :like",
				[
					':old' => $oldCode,
					':new' => $newCode,
					':like' => '%' . $oldCode . '%',
				]
			)->execute();
			echo "Updated $templates email template(s) referencing $oldCode\n";
		}
	}

	private function renameRecruitmentApplicationFieldLabel(): void
	{
		$tabId = (int) $this->db->createCommand(
			"SELECT tabid FROM vtiger_tab WHERE name = 'RecruitmentApplication'"
		)->queryScalar();
		if ($tabId <= 0) {
			return;
		}

		$updated = (int) $this->db->createCommand()->update(
			'vtiger_field',
			['fieldlabel' => 'FL_CANDIDATE'],
			['tabid' => $tabId, 'fieldname' => 'candidate_id', 'fieldlabel' => 'FL_KANDYDACI']
		)->execute();
		echo "Updated RecruitmentApplication candidate_id fieldlabel ($updated row(s))\n";
	}
}
