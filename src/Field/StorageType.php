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

namespace App\Field;

/**
 * Maps vtiger_field.typeofdata codes to storage_type column values.
 * Used by migrations/backfill; runtime reads storage_type from DB once wired (Phase 2b).
 */
final class StorageType
{
	/** @var array<string, string> typeofdata token → storage_type */
	private const FROM_TYPEOFDATA = [
		'V'  => 'string',
		'M'  => 'text',
		'D'  => 'date',
		'DT' => 'datetime',
		'T'  => 'time',
		'I'  => 'integer',
		'N'  => 'decimal',
		'NN' => 'float',
		'C'  => 'boolean',
		'E'  => 'email',
		'P'  => 'password',
	];

	public static function fromTypeofdata(string $typeofdata): string
	{
		$code = FieldDefinition::normalizeTypeofdata($typeofdata);
		if (!isset(self::FROM_TYPEOFDATA[$code])) {
			throw new \InvalidArgumentException("Unknown typeofdata code '{$code}'");
		}
		return self::FROM_TYPEOFDATA[$code];
	}
}
