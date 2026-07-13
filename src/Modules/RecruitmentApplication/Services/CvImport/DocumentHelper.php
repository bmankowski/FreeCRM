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

final class DocumentHelper
{
	public static function resolveCvFilePath(string $directory, string $cvSavedFilename, string $originalFilename): string
	{
		if ($cvSavedFilename !== '') {
			$path = $directory . $cvSavedFilename;
			if (is_file($path)) {
				return $path;
			}
		}
		if ($originalFilename !== '') {
			$path = $directory . basename($originalFilename);
			if (is_file($path)) {
				return $path;
			}
		}

		return '';
	}

	public static function resolveCvParsePath(CvApplicationDto $dto): ?string
	{
		if ($dto->cvAttachmentPath === '' || !is_file($dto->cvAttachmentPath)) {
			$resolved = self::resolveCvFilePath(
				$dto->pendingDirectory,
				$dto->cvSavedFilename,
				$dto->originalFilename
			);
			if ($resolved === '') {
				return null;
			}
			$dto->cvAttachmentPath = $resolved;
		}

		$attachmentPath = $dto->cvAttachmentPath;
		$originalPath = $dto->pendingDirectory . basename($dto->originalFilename);
		if ($attachmentPath !== $originalPath && $dto->originalFilename !== '') {
			copy($attachmentPath, $originalPath);
		}

		return is_file($originalPath) ? $originalPath : $attachmentPath;
	}

	public static function prepareRelationsString(string $moduleName, int $relatedEntityId): array
	{
		return [[
			'relatedModule' => $moduleName,
			'reverse' => 'true',
			'relatedRecords' => [(string) $relatedEntityId],
			'param' => ['PPL_APPLIED_BY_WEB'],
		]];
	}

	public static function saveAndDeleteFile(string $filepath, string $title, ?array $relations = null): \App\Modules\Base\Models\Record|false
	{
		$file = \App\Fields\File::loadFromPath($filepath);
		$fileName = $file->getName();
		$newDocument = \App\Modules\Documents\Models\Record::getCleanInstance('Documents');
		$automatUserId = \App\Modules\Users\Models\Record::getUserIdByName('automat');
		$newDocument->set('assigned_user_id', $automatUserId);
		$maxLength = $newDocument->getField('original_name')->get('maximumlength');
		$fileNameLength = mb_strlen((string) $fileName);
		if ($fileNameLength > $maxLength) {
			$extSuffix = '';
			$extLength = 0;
			if (!empty($ext = $file->getExtension())) {
				$extSuffix = ".{$ext}";
				$extLength = mb_strlen($extSuffix);
				if (str_ends_with($fileName, $extSuffix)) {
					$fileName = mb_substr($fileName, 0, mb_strlen($fileName) - $extLength);
				}
			}
			$baseMaxLength = $maxLength - $extLength;
			if ($baseMaxLength > 0) {
				$fileName = mb_substr($fileName, 0, $baseMaxLength) . $extSuffix;
			} else {
				$fileName = mb_substr($fileName . $extSuffix, 0, $maxLength);
			}
		}
		$fileName = \App\Security\Purifier::decodeHtml(\App\Security\Purifier::purify($fileName));
		$newDocument->set('notes_title', $title);
		$newDocument->set('original_name', $fileName);
		$newDocument->set('active', 1);
		$newDocument->set('location_type', 'internal');
		$newDocument->setPendingUploadFile([
			'name' => $fileName,
			'size' => $file->getSize(),
			'type' => $file->getMimeType(),
			'tmp_name' => $file->getPath(),
			'error' => 0,
		]);
		if ($relations !== null) {
			$newDocument->ext = ['relations' => $relations];
		}
		$request = new \App\Http\Vtiger_Request([], false);
		$request->set('module', 'Documents');
		$request->set('mode', '');
		$newDocument->save($request);
		if ((string) $newDocument->get('storage_path') !== '') {
			return $newDocument;
		}
		return false;
	}
}
