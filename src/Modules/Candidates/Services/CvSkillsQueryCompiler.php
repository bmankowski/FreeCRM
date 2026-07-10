<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Candidates\Services;

use yii\db\Expression;

class CvSkillsQueryCompiler
{
	private static int $paramCounter = 0;

	public static function compile(CvSkillsExpressionNode $node, string $column): array|Expression
	{
		self::$paramCounter = 0;
		$fulltext = self::tryCompileFulltext($node);
		if ($fulltext !== null) {
			return new Expression(
				'MATCH(' . $column . ') AGAINST(:cvSkillsFulltext0 IN BOOLEAN MODE)',
				[':cvSkillsFulltext0' => $fulltext]
			);
		}

		return self::compileNested($node, $column);
	}

	private static function tryCompileFulltext(CvSkillsExpressionNode $node): ?string
	{
		if (!self::subtreeUsesFulltextOnly($node)) {
			return null;
		}

		return self::compileFulltextRequired($node);
	}

	private static function subtreeUsesFulltextOnly(CvSkillsExpressionNode $node): bool
	{
		if ($node instanceof CvSkillsSkillNode) {
			return CvSkillsSearch::skillUsesFulltext($node->term);
		}
		if ($node instanceof CvSkillsAndNode || $node instanceof CvSkillsOrNode) {
			foreach ($node->children as $child) {
				if (!self::subtreeUsesFulltextOnly($child)) {
					return false;
				}
			}

			return true;
		}

		return false;
	}

	private static function compileFulltextRequired(CvSkillsExpressionNode $node): ?string
	{
		if ($node instanceof CvSkillsSkillNode) {
			return CvSkillsSearch::formatFulltextRequiredToken($node->term);
		}

		if ($node instanceof CvSkillsOrNode) {
			$alternatives = [];
			foreach ($node->children as $child) {
				$part = self::compileFulltextOrAlternative($child);
				if ($part === null || $part === '') {
					return null;
				}
				$alternatives[] = $part;
			}

			return '+( ' . implode(' ', $alternatives) . ')';
		}

		if ($node instanceof CvSkillsAndNode) {
			$parts = [];
			foreach ($node->children as $child) {
				$part = self::compileFulltextRequired($child);
				if ($part === null || $part === '') {
					return null;
				}
				$parts[] = $part;
			}

			return implode(' ', $parts);
		}

		return null;
	}

	private static function compileFulltextOrAlternative(CvSkillsExpressionNode $node): ?string
	{
		if ($node instanceof CvSkillsSkillNode) {
			return CvSkillsSearch::formatFulltextBareToken($node->term);
		}

		if ($node instanceof CvSkillsOrNode) {
			$alternatives = [];
			foreach ($node->children as $child) {
				$part = self::compileFulltextOrAlternative($child);
				if ($part === null || $part === '') {
					return null;
				}
				$alternatives[] = $part;
			}

			return '( ' . implode(' ', $alternatives) . ')';
		}

		if ($node instanceof CvSkillsAndNode) {
			$parts = [];
			foreach ($node->children as $child) {
				$part = self::compileFulltextRequired($child);
				if ($part === null || $part === '') {
					return null;
				}
				$parts[] = $part;
			}

			return '( ' . implode(' ', $parts) . ')';
		}

		return null;
	}

	private static function compileNested(CvSkillsExpressionNode $node, string $column): array|Expression
	{
		if ($node instanceof CvSkillsSkillNode) {
			return self::compileSkill($node->term, $column);
		}

		if ($node instanceof CvSkillsAndNode) {
			$conditions = [];
			foreach ($node->children as $child) {
				$conditions[] = self::compileNested($child, $column);
			}

			return array_merge(['and'], $conditions);
		}

		if ($node instanceof CvSkillsOrNode) {
			$conditions = [];
			foreach ($node->children as $child) {
				$conditions[] = self::compileNested($child, $column);
			}

			return array_merge(['or'], $conditions);
		}

		return ['=', $column, ''];
	}

	private static function compileSkill(string $term, string $column): array|Expression
	{
		if (CvSkillsSearch::skillUsesFulltext($term)) {
			$token = CvSkillsSearch::formatFulltextRequiredToken($term);
			if ($token !== '') {
				++self::$paramCounter;
				$param = ':cvSkillsFulltext' . self::$paramCounter;

				return new Expression(
					'MATCH(' . $column . ') AGAINST(' . $param . ' IN BOOLEAN MODE)',
					[$param => $token]
				);
			}
		}

		return ['REGEXP', $column, CvSkillsSearch::buildWordMatchRegexp($term)];
	}
}
