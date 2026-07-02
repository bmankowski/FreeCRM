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

class CvSkillsSearch
{
	public const FIELD_NAME = 'cv_text';

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

	/**
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

		$conditions = self::buildSearchParamConditions($raw);
		if ($conditions === []) {
			return;
		}

		$existing = $request->get('search_params');
		if (!\is_array($existing)) {
			$existing = [];
		}

		if ($existing === [] || !isset($existing[0]) || !\is_array($existing[0])) {
			$request->set('search_params', [$conditions]);
			return;
		}

		$existing[0] = array_merge($conditions, $existing[0]);
		$request->set('search_params', $existing);
	}
}
