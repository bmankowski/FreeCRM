<?php
/**
 * FreeCRM - Seed recruitment transitions for PPL_MANUALLY_ADDED when transition matrix is configured.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260605_000001_recruitment_status_manually_added extends Migration
{
	public function safeUp(): void
	{
		$configured = (int) (new Query())
			->select(['configured'])
			->from('u_yf_recruitment_settings')
			->where(['id' => 1])
			->scalar();

		if ($configured !== 1) {
			return;
		}

		$pairs = [
			['from_status' => 'PPL_MANUALLY_ADDED', 'to_status' => 'PPL_CANDIDATE_PASSED_SCREENING'],
			['from_status' => 'PPL_MANUALLY_ADDED', 'to_status' => 'PPL_REJECTED_AFTER_CV'],
		];

		foreach ($pairs as $pair) {
			$exists = (new Query())
				->from('u_yf_recruitment_status_transitions')
				->where($pair)
				->exists();
			if (!$exists) {
				$this->db->createCommand()->insert('u_yf_recruitment_status_transitions', $pair)->execute();
			}
		}
	}

	public function safeDown(): void
	{
		$this->db->createCommand()->delete('u_yf_recruitment_status_transitions', [
			'from_status' => 'PPL_MANUALLY_ADDED',
		])->execute();
	}
}
