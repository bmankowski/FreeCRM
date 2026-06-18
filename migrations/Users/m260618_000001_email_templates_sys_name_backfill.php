<?php
/**
 * Backfill u_yf_emailtemplates.sys_name from template name where empty.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260618_000001_email_templates_sys_name_backfill extends Migration
{
	public function safeUp(): void
	{
		$rows = (new Query())
			->select(['emailtemplatesid', 'name', 'sys_name'])
			->from('u_yf_emailtemplates')
			->innerJoin('vtiger_crmentity', 'u_yf_emailtemplates.emailtemplatesid = vtiger_crmentity.crmid')
			->where(['vtiger_crmentity.deleted' => 0])
			->all();

		$usedSet = [];
		foreach ($rows as $row) {
			$existing = trim((string) ($row['sys_name'] ?? ''));
			if ($existing !== '') {
				$usedSet[$existing] = true;
			}
		}

		foreach ($rows as $row) {
			$existing = trim((string) ($row['sys_name'] ?? ''));
			if ($existing !== '') {
				continue;
			}
			$id = (int) $row['emailtemplatesid'];
			$slug = $this->slugFromName((string) ($row['name'] ?? 'template'));
			if (isset($usedSet[$slug])) {
				$slug = substr($slug, 0, 40) . '_' . $id;
			}
			$usedSet[$slug] = true;
			$this->update('u_yf_emailtemplates', ['sys_name' => $slug], ['emailtemplatesid' => $id]);
		}
	}

	public function safeDown(): void
	{
		echo "m260618_000001: safeDown not supported — restore DB backup.\n";
	}

	private function slugFromName(string $name): string
	{
		$slug = mb_strtolower($name, 'UTF-8');
		$translit = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
		if ($translit !== false) {
			$slug = strtolower($translit);
		}
		$slug = preg_replace('/[^a-z0-9]+/u', '_', $slug) ?? '';
		$slug = trim($slug, '_');
		if ($slug === '') {
			$slug = 'template';
		}

		return substr($slug, 0, 50);
	}
}
