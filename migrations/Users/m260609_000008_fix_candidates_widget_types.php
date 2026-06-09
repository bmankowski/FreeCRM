<?php
/**
 * FreeCRM - Fix vtiger_widgets.type after Kandydaci → Candidates widget class rename.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260609_000008_fix_candidates_widget_types extends Migration
{
	private const TABID = 121;

	/** @var array<string, string> */
	private const TYPE_MAP = [
		'KandydaciPreview' => 'CandidatesPreview',
		'KandydaciRecruitmentProjects' => 'CandidatesRecruitmentProjects',
	];

	public function safeUp(): void
	{
		foreach (self::TYPE_MAP as $old => $new) {
			$count = $this->db->createCommand(
				'UPDATE vtiger_widgets SET type = :new WHERE tabid = :tabid AND type = :old',
				[':new' => $new, ':tabid' => self::TABID, ':old' => $old]
			)->execute();
			echo "Updated $count vtiger_widgets row(s): $old → $new\n";
		}

		\App\Cache\Cache::delete('ModuleWidgets', self::TABID);
	}

	public function safeDown(): void
	{
		echo "This migration is not reversible automatically.\n";
	}
}
