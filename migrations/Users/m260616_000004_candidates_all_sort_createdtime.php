<?php
/**
 * FreeCRM - Fix Candidates All filter sort after 000003 order bug (cvid 399).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260616_000004_candidates_all_sort_createdtime extends Migration
{
	public function safeUp(): void
	{
		$count = $this->db->createCommand()->update(
			'vtiger_customview',
			['sort' => 'createdtime,DESC'],
			['cvid' => 399, 'entitytype' => 'Candidates']
		)->execute();
		echo "Set Candidates All sort=createdtime,DESC on $count row(s)\n";
		\App\Cache\Cache::clear();
	}

	public function safeDown(): void
	{
		echo "This migration is not reversible automatically.\n";
	}
}
