<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Move footer dynamics ($(dynamic : …)$) from EmailTemplates content into the footer field.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260723_000004_emailtemplates_move_footer_from_content extends Migration
{
	/** @var list<string> */
	private const FOOTER_CODES = [
		'current_user_footer',
		'current_recruitment_group_footer',
		'candidates_unsubscribe_footer',
		'tracking_logo',
	];

	public function safeUp(): void
	{
		$this->moveFooters(false);
	}

	public function safeDown(): void
	{
		$this->moveFooters(true);
	}

	private function moveFooters(bool $down): void
	{
		$schema = $this->db->getTableSchema('u_yf_emailtemplates', true);
		if ($schema === null || !isset($schema->columns['footer'])) {
			return;
		}

		$rows = (new Query())
			->select(['emailtemplatesid', 'sys_name', 'content', 'footer'])
			->from('u_yf_emailtemplates')
			->all($this->db);

		$moved = 0;
		foreach ($rows as $row) {
			$id = (int) $row['emailtemplatesid'];
			$content = (string) ($row['content'] ?? '');
			$footer = (string) ($row['footer'] ?? '');

			if ($down) {
				if ($footer === '' || !$this->containsFooterDynamics($footer)) {
					continue;
				}
				if ($this->containsFooterDynamics($content)) {
					continue;
				}
				$merged = rtrim($content);
				if ($merged !== '') {
					$merged .= "\n\n";
				}
				$merged .= trim($footer);
				$this->update('u_yf_emailtemplates', [
					'content' => $merged,
					'footer' => null,
				], ['emailtemplatesid' => $id]);
				$moved++;
				echo "Reverted footer into content on EmailTemplate {$id} ({$row['sys_name']}).\n";
				continue;
			}

			if (!$this->containsFooterDynamics($content)) {
				continue;
			}
			if ($footer !== '' && $this->containsFooterDynamics($footer)) {
				echo "Skip {$id}: footer already set.\n";
				continue;
			}

			[$newContent, $extracted] = $this->extractFooterFromContent($content);
			if ($extracted === '') {
				continue;
			}

			$this->update('u_yf_emailtemplates', [
				'content' => $newContent,
				'footer' => $extracted,
			], ['emailtemplatesid' => $id]);
			$moved++;
			echo "Moved footer from content on EmailTemplate {$id} ({$row['sys_name']}).\n";
		}

		echo "Moved footers on {$moved} EmailTemplate(s).\n";
		\App\Email\Mail::clearTemplateListCache();
	}

	private function containsFooterDynamics(string $html): bool
	{
		foreach (self::FOOTER_CODES as $code) {
			if (str_contains($html, '$(dynamic : ' . $code . ')$')) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array{0: string, 1: string} [content without footers, extracted footer]
	 */
	private function extractFooterFromContent(string $content): array
	{
		$found = [];
		$pattern = '/\$\(dynamic\s*:\s*(' . implode('|', array_map('preg_quote', self::FOOTER_CODES)) . ')\)\$/';
		if (preg_match_all($pattern, $content, $matches)) {
			foreach ($matches[1] as $code) {
				$token = '$(dynamic : ' . $code . ')$';
				if (!in_array($token, $found, true)) {
					$found[] = $token;
				}
			}
		}

		$cleaned = preg_replace($pattern, '', $content) ?? $content;
		$cleaned = preg_replace("/[ \t]*\n(?:[ \t]*\n){2,}/", "\n\n", $cleaned) ?? $cleaned;
		$cleaned = rtrim($cleaned);

		return [$cleaned, implode("\n", $found)];
	}
}
