<?php
/**
 * FreeCRM - Rename user footer TemplateElements: current_footer/standard_footer → *_user_footer.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260720_000004_rename_user_footer_template_elements extends Migration
{
	private const RENAMES = [
		'standard_footer' => [
			'new' => 'standard_user_footer',
			'label' => 'Standard user footer',
			'description' => 'Standardowa stopka użytkownika',
		],
		'current_footer' => [
			'new' => 'current_user_footer',
			'label' => 'Current user footer',
			'description' => 'Alias stopki użytkownika → standard_user_footer',
		],
	];

	/** @var list<array{0: string, 1: string}> */
	private const TEXT_REPLACEMENTS = [
		['$(dynamic : standard_footer)$', '$(dynamic : standard_user_footer)$'],
		['$(dynamic : current_footer)$', '$(dynamic : current_user_footer)$'],
	];

	public function safeUp(): void
	{
		$this->renameElements(false);
		$this->replaceReferences(false);
		\App\Email\Mail::clearTemplateListCache();
	}

	public function safeDown(): void
	{
		$this->replaceReferences(true);
		$this->renameElements(true);
		\App\Email\Mail::clearTemplateListCache();
	}

	private function renameElements(bool $down): void
	{
		if ($this->db->getTableSchema('u_yf_templateelements', true) === null) {
			return;
		}

		$pairs = self::RENAMES;
		if ($down) {
			$pairs = array_reverse($pairs, true);
		}

		foreach ($pairs as $oldCode => $meta) {
			$from = $down ? $meta['new'] : $oldCode;
			$to = $down ? $oldCode : $meta['new'];
			$id = (int) (new Query())
				->select(['templateelementsid'])
				->from('u_yf_templateelements')
				->where(['code' => $from])
				->scalar($this->db);
			if ($id <= 0) {
				echo "Skip rename: {$from} not found.\n";
				continue;
			}

			$update = ['code' => $to];
			if (!$down) {
				$update['label'] = $meta['label'];
				$update['description'] = $meta['description'];
			} else {
				$update['label'] = $from === 'standard_user_footer' ? 'Standard footer' : 'Current footer';
				$update['description'] = $from === 'standard_user_footer' ? 'Standardowa stopka' : '';
			}

			$this->update('u_yf_templateelements', $update, ['templateelementsid' => $id]);
			$this->update('vtiger_crmentity', [
				'description' => $to,
			], ['crmid' => $id, 'setype' => 'TemplateElements']);
			echo "Renamed TemplateElement {$id}: {$from} → {$to}.\n";
		}
	}

	private function replaceReferences(bool $down): void
	{
		$replacements = self::TEXT_REPLACEMENTS;
		if ($down) {
			$replacements = array_map(static fn (array $pair): array => [$pair[1], $pair[0]], $replacements);
		}

		$this->replaceInTable('u_yf_templateelements', ['content'], $replacements);
		$this->replaceInTable('u_yf_emailtemplates', ['content', 'footer'], $replacements);
		$this->replaceInTable('u_yf_documenttemplates', ['header_content', 'body_content', 'footer_content'], $replacements);
	}

	/**
	 * @param list<string> $columns
	 * @param list<array{0: string, 1: string}> $replacements
	 */
	private function replaceInTable(string $table, array $columns, array $replacements): void
	{
		if ($this->db->getTableSchema($table, true) === null) {
			return;
		}

		foreach ($columns as $column) {
			foreach ($replacements as [$from, $to]) {
				$sql = 'UPDATE `' . str_replace('`', '``', $table) . '` SET `'
					. str_replace('`', '``', $column) . '` = REPLACE(`'
					. str_replace('`', '``', $column) . '`, :from, :to) WHERE `'
					. str_replace('`', '``', $column) . '` LIKE :like';
				$n = $this->db->createCommand($sql, [
					':from' => $from,
					':to' => $to,
					':like' => '%' . $from . '%',
				])->execute();
				if ($n > 0) {
					echo "Updated {$table}.{$column}: {$n} row(s) ({$from} → {$to}).\n";
				}
			}
		}
	}
}
