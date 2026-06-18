<?php
/**
 * Fix sys_name slugs broken by missing transliteration in m260618_000001.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260618_000002_email_templates_sys_name_transliteration_fix extends Migration
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
				$usedSet[$existing] = (int) $row['emailtemplatesid'];
			}
		}

		foreach ($rows as $row) {
			$name = (string) ($row['name'] ?? 'template');
			$current = trim((string) ($row['sys_name'] ?? ''));
			$broken = $this->brokenSlugFromName($name);
			$fixed = $this->slugFromName($name);
			if ($current === '' || $current !== $broken || $broken === $fixed) {
				continue;
			}

			$id = (int) $row['emailtemplatesid'];
			unset($usedSet[$current]);
			$slug = $fixed;
			if (isset($usedSet[$slug]) && $usedSet[$slug] !== $id) {
				$slug = substr($slug, 0, 40) . '_' . $id;
			}
			$usedSet[$slug] = $id;
			$this->update('u_yf_emailtemplates', ['sys_name' => $slug], ['emailtemplatesid' => $id]);
		}
	}

	public function safeDown(): void
	{
		echo "m260618_000002: safeDown not supported — restore DB backup.\n";
	}

	private function brokenSlugFromName(string $name): string
	{
		$slug = mb_strtolower($name, 'UTF-8');
		$slug = preg_replace('/[^a-z0-9]+/u', '_', $slug) ?? '';
		$slug = trim($slug, '_');

		return substr($slug !== '' ? $slug : 'template', 0, 50);
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
