<?php
/**
 * FreeCRM - Documents getAttachments for modules with Documents summary widget.
 *
 * m260613_000006 restored getAttachments on core entity modules but omitted several
 * modules that already have a Documents RelatedModule widget on DetailView summary.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260714_000001_documents_getattachments_widget_modules extends Migration
{
	private const DOCUMENTS_TABID = 8;

	public function safeUp(): void
	{
		$nextRelationId = (int) (new Query())->from('vtiger_relatedlists')->max('relation_id') + 1;
		$added = 0;

		$modules = (new Query())
			->select(['t.tabid', 't.name'])
			->from(['w' => 'vtiger_widgets'])
			->innerJoin(['t' => 'vtiger_tab'], 't.tabid = w.tabid')
			->where([
				'w.type' => 'RelatedModule',
				't.isentitytype' => 1,
			])
			->andWhere(['<>', 't.presence', 1])
			->andWhere([
				'or',
				['like', 'w.data', '"relatedmodule":"8"'],
				['like', 'w.data', '"relatedmodule":8'],
			])
			->groupBy(['t.tabid', 't.name'])
			->all();

		foreach ($modules as $row) {
			$tabid = (int) $row['tabid'];
			$moduleName = (string) $row['name'];

			$exists = (new Query())
				->from('vtiger_relatedlists')
				->where(['tabid' => $tabid, 'related_tabid' => self::DOCUMENTS_TABID])
				->exists();
			if ($exists) {
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
			echo "Added Documents getAttachments for {$moduleName}\n";
		}

		echo "Added {$added} Documents getAttachments related list(s)\n";
	}

	public function safeDown(): void
	{
		$tabids = (new Query())
			->select(['t.tabid'])
			->from(['w' => 'vtiger_widgets'])
			->innerJoin(['t' => 'vtiger_tab'], 't.tabid = w.tabid')
			->where([
				'w.type' => 'RelatedModule',
				't.isentitytype' => 1,
			])
			->andWhere(['<>', 't.presence', 1])
			->andWhere([
				'or',
				['like', 'w.data', '"relatedmodule":"8"'],
				['like', 'w.data', '"relatedmodule":8'],
			])
			->groupBy(['t.tabid'])
			->column();

		if ($tabids === []) {
			return;
		}

		$this->delete('vtiger_relatedlists', [
			'tabid' => $tabids,
			'related_tabid' => self::DOCUMENTS_TABID,
			'name' => 'getAttachments',
		]);
	}
}
