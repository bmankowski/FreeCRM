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
		$processed = CvFilePaths::processed();
		if (is_file($dto->jsonFilePath)) {
			rename($dto->jsonFilePath, $processed . basename($dto->jsonFilePath));
		}
		if ($dto->cvAttachmentPath !== '' && is_file($dto->cvAttachmentPath)) {
			rename($dto->cvAttachmentPath, $processed . basename($dto->cvAttachmentPath));
		}
	}

	public static function moveToFailed(CvApplicationDto $dto): void
	{
		CvImportLogger::log('Moving files to failed');
		$failed = CvFilePaths::failed();
		if (is_file($dto->jsonFilePath)) {
			rename($dto->jsonFilePath, $failed . basename($dto->jsonFilePath));
		}
		if ($dto->cvAttachmentPath !== '' && is_file($dto->cvAttachmentPath)) {
			rename($dto->cvAttachmentPath, $failed . basename($dto->cvAttachmentPath));
		}
	}
}
