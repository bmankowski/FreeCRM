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

class CvSkillsSearch
{
	public const FIELD_NAME = 'cv_text';

	public const FULLTEXT_MIN_TOKEN_LENGTH = 3;

	private static string $pendingWordMatchRaw = '';

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
		$raw = trim($raw);
		if ($raw === '') {
			return;
		}

		$ast = CvSkillsExpressionParser::parse($raw);
		self::ensureCvTextTableJoined($queryGenerator);

		$column = $queryGenerator->getColumnName(self::FIELD_NAME);
		$queryGenerator->addNativeCondition(
			CvSkillsQueryCompiler::compile($ast, $column)
		);
	}

	/**
	 * @return list<string>
	 */
	public static function collectTermsForHighlight(string $raw): array
	{
		$raw = trim($raw);
		if ($raw === '') {
			return [];
		}

		return CvSkillsExpressionParser::collectTerms(
			CvSkillsExpressionParser::parse($raw)
		);
	}

	public static function validateExpression(string $raw): void
	{
		CvSkillsExpressionParser::parse(trim($raw));
	}

	public static function skillUsesFulltext(string $skill): bool
	{
		return mb_strlen($skill) >= self::FULLTEXT_MIN_TOKEN_LENGTH;
	}

	public static function formatFulltextRequiredToken(string $skill): string
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

	public static function formatFulltextBareToken(string $skill): string
	{
		if (preg_match('/[+\-><()~*"@]/u', $skill) || str_contains($skill, ' ')) {
			return '"' . str_replace(['\\', '"'], ['\\\\', ''], $skill) . '"';
		}

		return $skill;
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
		return '[[:<:]]' . preg_quote($skill, '/') . '[[:>:]]';
	}

	public static function buildWordMatchPcrePattern(string $skill): string
	{
		return '/(?<![[:alnum:]])' . preg_quote($skill, '/') . '(?![[:alnum:]])/iu';
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
