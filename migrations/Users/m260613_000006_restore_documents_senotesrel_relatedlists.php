<?php
/**
 * FreeCRM - Restore Documents related lists (getAttachments / vtiger_senotesrel).
 *
 * m260613_000003 removed all getAttachments rows; senotesrel data stayed intact.
 * EmailTemplates keeps getManyToMany (u_yf_documents_emailtemplates) — not touched here.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260613_000006_restore_documents_senotesrel_relatedlists extends Migration
{
	private const DOCUMENTS_TABID = 8;

	/** @var array<int, string> tabid => module name (active entity modules using senotesrel) */
	private const MODULES = [
		121 => 'Candidates',
		119 => 'ProjektyRekrutacyjne',
		6 => 'Accounts',
		4 => 'Contacts',
		7 => 'Leads',
		13 => 'HelpDesk',
		43 => 'Project',
		14 => 'Products',
		35 => 'Services',
		15 => 'Faq',
		37 => 'Assets',
		34 => 'ServiceContracts',
		59 => 'OutsourcedProducts',
		18 => 'Vendors',
		26 => 'Campaigns',
		127 => 'DocumentTemplates',
	];

	public function safeUp(): void
	{
		$nextRelationId = (int) (new Query())->from('vtiger_relatedlists')->max('relation_id') + 1;
		$added = 0;

		foreach (self::MODULES as $tabid => $moduleName) {
			if (!$this->isActiveEntityModule($tabid, $moduleName)) {
				echo "Skip Documents relation for {$moduleName} (tabid {$tabid}): module inactive or missing\n";
				continue;
			}

			$exists = (new Query())
				->from('vtiger_relatedlists')
				->where([
					'tabid' => $tabid,
					'related_tabid' => self::DOCUMENTS_TABID,
					'name' => 'getAttachments',
				])
				->exists();
			if ($exists) {
				continue;
			}

			$hasOtherDocumentsRelation = (new Query())
				->from('vtiger_relatedlists')
				->where(['tabid' => $tabid, 'related_tabid' => self::DOCUMENTS_TABID])
				->exists();
			if ($hasOtherDocumentsRelation) {
				echo "Skip Documents getAttachments for {$moduleName}: another Documents relation already registered\n";
				continue;
			}

			$sequence = (int) (new Query())
				->from('vtiger_relatedlists')
				->where(['tabid' => $tabid])
				->max('sequence');
			$sequence = $sequence > 0 ? $sequence + 1 : 1;

			$this->insert('vtiger_relatedlists', [
				'relation_id' => $nextRelationId++,
				'tabid' => $tabid,
				'related_tabid' => self::DOCUMENTS_TABID,
				'name' => 'getAttachments',
				'sequence' => $sequence,
				'label' => 'Documents',
				'presence' => 0,
				'actions' => 'ADD,SELECT',
				'favorites' => 0,
				'creator_detail' => 0,
				'relation_comment' => 0,
			]);
			++$added;
		}

		echo "Added {$added} Documents getAttachments related list(s)\n";
	}

	public function safeDown(): void
	{
		$tabids = array_keys(self::MODULES);
		$this->delete('vtiger_relatedlists', [
			'tabid' => $tabids,
			'related_tabid' => self::DOCUMENTS_TABID,
			'name' => 'getAttachments',
		]);
	}

	private function isActiveEntityModule(int $tabid, string $moduleName): bool
	{
		$row = (new Query())
			->select(['presence', 'isentitytype'])
			->from('vtiger_tab')
			->where(['tabid' => $tabid, 'name' => $moduleName])
			->one();

		return is_array($row)
			&& (int) ($row['isentitytype'] ?? 0) === 1
			&& (int) ($row['presence'] ?? 1) !== 1;
	}
}
