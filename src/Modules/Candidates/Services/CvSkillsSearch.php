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

use App\Http\Vtiger_Request;
use App\QueryField\QueryGenerator;
use yii\db\Expression;

class CvSkillsSearch
{
	public const FIELD_NAME = 'cv_text';

	private const FULLTEXT_MIN_TOKEN_LENGTH = 3;

	private static string $pendingWordMatchRaw = '';

	/**
	 * @return list<string>
	 */
	public static function parseSkills(string $raw): array
	{
		$parts = preg_split('/[;]+/u', $raw) ?: [];
		$skills = [];
		foreach ($parts as $part) {
			$skill = trim($part);
			if ($skill !== '') {
				$skills[] = $skill;
			}
		}

		return array_values(array_unique($skills));
	}

	public static function setPendingWordMatch(string $raw): void
	{
		self::$pendingWordMatchRaw = trim($raw);
	}

	public static function consumePendingWordMatch(QueryGenerator $queryGenerator): void
	{
		if (self::$pendingWordMatchRaw === '') {
			return;
		}
		self::applyWordMatchToQueryGenerator($queryGenerator, self::$pendingWordMatchRaw);
		self::$pendingWordMatchRaw = '';
	}

	public static function applyWordMatchToQueryGenerator(QueryGenerator $queryGenerator, string $raw): void
	{
		$skills = self::parseSkills($raw);
		if ($skills === []) {
			return;
		}

		self::ensureCvTextTableJoined($queryGenerator);

		$column = $queryGenerator->getColumnName(self::FIELD_NAME);
		$fulltextSkills = [];
		$regexpSkills = [];
		foreach ($skills as $skill) {
			if (mb_strlen($skill) < self::FULLTEXT_MIN_TOKEN_LENGTH) {
				$regexpSkills[] = $skill;
			} else {
				$fulltextSkills[] = $skill;
			}
		}

		$booleanQuery = self::buildFulltextBooleanQuery($fulltextSkills);
		if ($booleanQuery !== '') {
			$queryGenerator->addNativeCondition(new Expression(
				'MATCH(' . $column . ') AGAINST(:cvSkillsFulltext IN BOOLEAN MODE)',
				[':cvSkillsFulltext' => $booleanQuery]
			));
		}

		foreach ($regexpSkills as $skill) {
			$queryGenerator->addNativeCondition([
				'REGEXP',
				$column,
				self::buildWordMatchRegexp($skill),
			]);
		}
	}

	/**
	 * @param list<string> $skills
	 */
	public static function buildFulltextBooleanQuery(array $skills): string
	{
		$parts = [];
		foreach ($skills as $skill) {
			$token = self::formatFulltextRequiredToken($skill);
			if ($token !== '') {
				$parts[] = $token;
			}
		}

		return implode(' ', $parts);
	}

	private static function formatFulltextRequiredToken(string $skill): string
	{
		if (preg_match('/[+\-><()~*"@]/u', $skill)) {
			return '+"' . str_replace(['\\', '"'], ['\\\\', ''], $skill) . '"';
		}

		$words = preg_split('/\s+/u', trim($skill)) ?: [];
		$tokens = [];
		foreach ($words as $word) {
			if ($word === '' || mb_strlen($word) < self::FULLTEXT_MIN_TOKEN_LENGTH) {
				continue;
			}
			$tokens[] = '+' . $word;
		}

		return implode(' ', $tokens);
	}

	private static function ensureCvTextTableJoined(QueryGenerator $queryGenerator): void
	{
		$field = $queryGenerator->getModuleField(self::FIELD_NAME);
		if ($field) {
			$queryGenerator->addTableToQuery($field->getTableName());
		}
	}

	public static function buildWordMatchRegexp(string $skill): string
	{
		return '[[:<:]]' . self::escapeRegexpLiteral($skill) . '[[:>:]]';
	}

	public static function buildWordMatchPcrePattern(string $skill): string
	{
		return '/(?<![[:alnum:]])' . preg_quote($skill, '/') . '(?![[:alnum:]])/iu';
	}

	private static function escapeRegexpLiteral(string $value): string
	{
		return preg_quote($value, '/');
	}

	/**
	 * @deprecated Use word-match via setPendingWordMatch / applyWordMatchToQueryGenerator
	 * @return list<array{0: string, 1: string, 2: string}>
	 */
	public static function buildSearchParamConditions(string $raw): array
	{
		$conditions = [];
		foreach (self::parseSkills($raw) as $skill) {
			$conditions[] = [self::FIELD_NAME, 'c', $skill];
		}

		return $conditions;
	}

	public static function applyToRequest(Vtiger_Request $request): void
	{
		$raw = trim((string) $request->get('cv_skills'));
		if ($raw === '') {
			return;
		}

		self::setPendingWordMatch($raw);
	}
}
