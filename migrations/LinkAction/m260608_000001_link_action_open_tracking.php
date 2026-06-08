<?php
/**
 * FreeCRM - LinkAction open tracking: template element seed.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/LinkAction/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260608_000001_link_action_open_tracking extends Migration
{
	private const ELEMENT_CODE = 'kandydaci_open_tracking_logo';

	private const ELEMENTS = [
		'pl_pl' => [
			'content' => '<img src="$(custom : LinkActionImageUrl|open|email|email_prywatny)$" width="120" height="40" alt="IT Connect" style="display:block;">',
			'description' => 'Logo ze śledzeniem otwarć e-mail dla modułu Kandydaci (PL).',
		],
		'en_us' => [
			'content' => '<img src="$(custom : LinkActionImageUrl|open|email|email_prywatny)$" width="120" height="40" alt="IT Connect" style="display:block;">',
			'description' => 'Open-tracking logo for Kandydaci module emails (EN).',
		],
	];

	public function safeUp(): void
	{
		$this->seedTemplateElements();
	}

	public function safeDown(): void
	{
		$this->delete('u_yf_templateelements', ['code' => self::ELEMENT_CODE]);
		$this->delete('vtiger_crmentity', ['setype' => 'TemplateElements', 'description' => self::ELEMENT_CODE]);
	}

	private function seedTemplateElements(): void
	{
		if ($this->db->getSchema()->getTableSchema('u_yf_templateelements', true) === null) {
			return;
		}

		foreach (self::ELEMENTS as $language => $element) {
			if ((new Query())->from('u_yf_templateelements')->where([
				'code' => self::ELEMENT_CODE,
				'module_name' => 'Kandydaci',
				'language' => $language,
			])->exists()) {
				continue;
			}

			$id = $this->nextEntityId();
			$now = date('Y-m-d H:i:s');
			$this->insert('u_yf_templateelements', [
				'templateelementsid' => $id,
				'code' => self::ELEMENT_CODE,
				'label' => 'LBL_KANDYDACI_OPEN_TRACKING_LOGO',
				'type' => 'PLL_VARIABLE_ALIAS',
				'module_name' => 'Kandydaci',
				'language' => $language,
				'status' => 1,
				'sequence' => 11,
				'content' => $element['content'],
				'layout_header' => '',
				'layout_body' => '',
				'layout_footer' => '',
				'description' => $element['description'],
			]);
			$this->insert('vtiger_crmentity', [
				'crmid' => $id,
				'smcreatorid' => 1,
				'smownerid' => 1,
				'shownerid' => 0,
				'modifiedby' => 1,
				'setype' => 'TemplateElements',
				'description' => self::ELEMENT_CODE,
				'createdtime' => $now,
				'modifiedtime' => $now,
				'presence' => 1,
				'deleted' => 0,
				'was_read' => 0,
				'private' => 0,
			]);
		}
	}

	private function nextEntityId(): int
	{
		$maxElement = (int) (new Query())->from('u_yf_templateelements')->max('templateelementsid', $this->db);
		$maxCrm = (int) (new Query())->from('vtiger_crmentity')->max('crmid', $this->db);
		return max($maxElement, $maxCrm) + 1;
	}
}
