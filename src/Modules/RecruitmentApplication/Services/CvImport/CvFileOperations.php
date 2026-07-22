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

final class CvFileOperations
{
	public static function deleteFiles(CvApplicationDto $dto): void
	{
		if (is_file($dto->jsonFilePath)) {
			unlink($dto->jsonFilePath);
		}
		if ($dto->cvAttachmentPath !== '' && is_file($dto->cvAttachmentPath)) {
			unlink($dto->cvAttachmentPath);
		}
	}

	public static function moveToProcessed(CvApplicationDto $dto): void
	{
		CvImportLogger::log('Moving files to processed');
		self::moveDtoFiles($dto, CvFilePaths::processed());
	}

	public static function moveToFailed(CvApplicationDto $dto): void
	{
		CvImportLogger::log('Moving files to failed');
		self::moveDtoFiles($dto, CvFilePaths::failed());
	}

	private static function moveDtoFiles(CvApplicationDto $dto, string $destDir): void
	{
		if (is_file($dto->jsonFilePath)) {
			self::renameOrFail($dto->jsonFilePath, $destDir . basename($dto->jsonFilePath));
		}
		if ($dto->cvAttachmentPath !== '' && is_file($dto->cvAttachmentPath)) {
			self::renameOrFail($dto->cvAttachmentPath, $destDir . basename($dto->cvAttachmentPath));
		}
	}

	private static function renameOrFail(string $from, string $to): void
	{
		if (!rename($from, $to)) {
			throw new \RuntimeException(sprintf('Failed to move %s → %s', $from, $to));
		}
	}
}
