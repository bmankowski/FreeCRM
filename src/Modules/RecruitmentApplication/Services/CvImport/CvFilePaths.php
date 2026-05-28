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

namespace App\Modules\RecruitmentApplication\Services\CvImport;

final class CvFilePaths
{
	public static function base(): string
	{
		return rtrim(ROOT_DIRECTORY, '/') . '/import/cv/';
	}

	public static function pending(): string
	{
		return self::base() . 'pending/';
	}

	public static function processed(): string
	{
		return self::base() . 'processed/';
	}

	public static function failed(): string
	{
		return self::base() . 'failed/';
	}

	public static function backup(): string
	{
		return self::base() . 'backup/';
	}

	public static function lockFile(): string
	{
		return self::base() . '.import.lock';
	}

	public static function ensureDirectories(): void
	{
		foreach ([self::pending(), self::processed(), self::failed()] as $dir) {
			if (!is_dir($dir)) {
				mkdir($dir, 0755, true);
			}
		}
	}
}
