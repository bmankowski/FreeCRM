<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\EmailTemplates\Models;

class TemplateModule
{
	public const SEPARATOR = ' |##| ';

	/**
	 * @return list<string>
	 */
	public static function parse(mixed $value): array
	{
		if (is_array($value)) {
			return array_values(array_unique(array_filter(array_map('trim', $value), static fn (string $v): bool => $v !== '')));
		}
		$value = trim((string) $value);
		if ($value === '') {
			return [];
		}
		if (str_contains($value, self::SEPARATOR)) {
			return array_values(array_unique(array_filter(
				array_map('trim', explode(self::SEPARATOR, $value)),
				static fn (string $v): bool => $v !== ''
			)));
		}

		return [$value];
	}

	public static function isGlobal(mixed $value): bool
	{
		return self::parse($value) === [];
	}

	public static function hasModule(mixed $value, string $moduleName): bool
	{
		return in_array($moduleName, self::parse($value), true);
	}

	/**
	 * @param list<string> $modules
	 */
	public static function encode(array $modules): string
	{
		$modules = array_values(array_unique(array_filter(array_map('trim', $modules), static fn (string $v): bool => $v !== '')));

		return $modules === [] ? '' : implode(self::SEPARATOR, $modules);
	}

	/**
	 * @return array<int|string, mixed>
	 */
	public static function sqlMatchesColumn(string $column, string $moduleName): array
	{
		return [
			'or',
			[$column => $moduleName],
			['like', $column, $moduleName . self::SEPARATOR . '%', false],
			['like', $column, '%' . self::SEPARATOR . $moduleName . self::SEPARATOR . '%', false],
			['like', $column, '%' . self::SEPARATOR . $moduleName, false],
		];
	}

	/**
	 * Global templates (empty modules) or templates assigned to $moduleName.
	 *
	 * @return array<int|string, mixed>
	 */
	public static function sqlGlobalOrMatches(string $column, string $moduleName): array
	{
		return [
			'or',
			['or', [$column => null], [$column => '']],
			self::sqlMatchesColumn($column, $moduleName),
		];
	}

	/**
	 * @param list<string> $moduleNames
	 * @return array<int|string, mixed>
	 */
	public static function sqlGlobalOrMatchesAny(string $column, array $moduleNames): array
	{
		$moduleNames = array_values(array_unique(array_filter(array_map('trim', $moduleNames))));
		if ($moduleNames === []) {
			return ['or', [$column => null], [$column => '']];
		}
		$conditions = ['or', ['or', [$column => null], [$column => '']]];
		foreach ($moduleNames as $moduleName) {
			$conditions[] = self::sqlMatchesColumn($column, $moduleName);
		}

		return $conditions;
	}
}
