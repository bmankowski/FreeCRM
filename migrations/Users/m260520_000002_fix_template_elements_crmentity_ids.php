<?php
/**
 * FreeCRM - Assign unique vtiger_crmentity rows for TemplateElements (avoid ID clash with DocumentTemplates).
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260520_000002_fix_template_elements_crmentity_ids extends Migration
{
	public function safeUp(): void
	{
		if ($this->db->getTableSchema('u_yf_templateelements', true) === null) {
			return;
		}

		$rows = (new Query())
			->from('u_yf_templateelements')
			->orderBy(['templateelementsid' => SORT_ASC])
			->all($this->db);
		if (!$rows) {
			return;
		}

		$nextId = (int) (new Query())->from('vtiger_crmentity')->max('crmid', $this->db);
		$maxElementId = (int) (new Query())->from('u_yf_templateelements')->max('templateelementsid', $this->db);
		$nextId = max($nextId, $maxElementId) + 1;
		$now = date('Y-m-d H:i:s');

		foreach ($rows as $row) {
			$oldId = (int) $row['templateelementsid'];
			$crmentity = (new Query())
				->select(['setype', 'deleted'])
				->from('vtiger_crmentity')
				->where(['crmid' => $oldId])
				->one($this->db);

			$targetId = $oldId;
			if ($crmentity && ($crmentity['setype'] ?? '') === 'TemplateElements') {
				continue;
			}

			if ($crmentity && ($crmentity['setype'] ?? '') !== 'TemplateElements') {
				$targetId = $nextId++;
				$this->update(
					'u_yf_templateelements',
					['templateelementsid' => $targetId],
					['templateelementsid' => $oldId]
				);
			}

			if ((new Query())->from('vtiger_crmentity')->where([
				'crmid' => $targetId,
				'setype' => 'TemplateElements',
			])->exists($this->db)) {
				continue;
			}

			if ((new Query())->from('vtiger_crmentity')->where(['crmid' => $targetId])->exists($this->db)) {
				$targetId = $nextId++;
				$this->update(
					'u_yf_templateelements',
					['templateelementsid' => $targetId],
					['templateelementsid' => $oldId]
				);
			}

			$this->insert('vtiger_crmentity', [
				'crmid' => $targetId,
				'smcreatorid' => 1,
				'smownerid' => 1,
				'shownerid' => 0,
				'modifiedby' => 1,
				'setype' => 'TemplateElements',
				'description' => null,
				'createdtime' => $now,
				'modifiedtime' => $now,
				'presence' => 1,
				'deleted' => 0,
				'was_read' => 0,
				'private' => 0,
			]);
		}
	}

	public function safeDown(): void
	{
		$this->delete('vtiger_crmentity', ['setype' => 'TemplateElements']);
	}
}
