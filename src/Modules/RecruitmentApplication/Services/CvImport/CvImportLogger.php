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

final class CvImportLogger
{
	public static function log(string $message, bool $condition = true): void
	{
		if (!$condition) {
			return;
		}
		echo $message . "\n";
		\App\Log\Log::info($message, 'cv-import');
	}
}
