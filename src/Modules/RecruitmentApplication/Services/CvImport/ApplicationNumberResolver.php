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

final class ApplicationNumberResolver
{
	public static function fromJsonPath(string $jsonFilePath): string
	{
		$jsonFilename = basename($jsonFilePath, '.json');
		if (strpos($jsonFilename, '_') === false) {
			return $jsonFilename;
		}
		$parts = explode('_', $jsonFilename);
		if ($parts[0] === 'apply' && isset($parts[1]) && ctype_digit($parts[1])) {
			return $parts[1];
		}
		return $jsonFilename;
	}
}
