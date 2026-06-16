<?php
/**
 * FreeCRM - Show CV preview first on Candidates summary; clearer widget label.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260616_000002_candidates_preview_widget_order extends Migration
{
	private const TABID = 121;

	/** @var array<string, int> widget type => sequence */
	private const SEQUENCE = [
		'CandidatesPreview' => 0,
		'CandidatesRecruitmentProjects' => 1,
		'Comments' => 2,
	];

	public function safeUp(): void
	{
		foreach (self::SEQUENCE as $type => $sequence) {
			$count = $this->db->createCommand(
				'UPDATE vtiger_widgets SET sequence = :sequence WHERE tabid = :tabid AND type = :type',
				[':sequence' => $sequence, ':tabid' => self::TABID, ':type' => $type]
			)->execute();
			echo "Set sequence=$sequence on $count row(s) for type $type\n";
		}

		$this->db->createCommand(
			'UPDATE vtiger_widgets SET label = :label WHERE tabid = :tabid AND type = :type',
			[':label' => 'Podgląd CV', ':tabid' => self::TABID, ':type' => 'CandidatesPreview']
		)->execute();

		\App\Cache\Cache::delete('ModuleWidgets', self::TABID);
	}

	public function safeDown(): void
	{
		echo "This migration is not reversible automatically.\n";
	}
}
