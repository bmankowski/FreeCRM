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

namespace App\Models;

class RecordFile
{
	public const ROLE_ATTACHMENT = 'attachment';
	public const ROLE_IMAGE = 'image';

	public static function resolveAbsolutePath(?string $storagePath): string|false
	{
		if ($storagePath === null || $storagePath === '') {
			return false;
		}

		return realpath(ROOT_DIRECTORY . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storagePath));
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function getByRecord(int $crmRecordId, string $role = self::ROLE_IMAGE): ?array
	{
		if ($crmRecordId <= 0) {
			return null;
		}
		$row = (new \App\Db\Query())
			->from('s_yf_record_files')
			->where(['crm_record_id' => $crmRecordId, 'role' => $role])
			->orderBy(['id' => SORT_DESC])
			->one();

		return $row ?: null;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public static function listByRecord(int $crmRecordId, ?string $role = null): array
	{
		if ($crmRecordId <= 0) {
			return [];
		}
		$query = (new \App\Db\Query())
			->from('s_yf_record_files')
			->where(['crm_record_id' => $crmRecordId])
			->orderBy(['id' => SORT_DESC]);
		if ($role !== null) {
			$query->andWhere(['role' => $role]);
		}

		return $query->all();
	}

	public static function saveUploadedFile(
		int $crmRecordId,
		array $fileDetails,
		string $moduleName,
		string $role = self::ROLE_ATTACHMENT,
		bool $replace = true
	): bool {
		if ($crmRecordId <= 0) {
			return false;
		}
		$fileInstance = \App\Fields\File::loadFromRequest($fileDetails);
		if (!$fileInstance->validate($role === self::ROLE_IMAGE ? 'image' : null)) {
			return false;
		}

		$fileName = isset($fileDetails['original_name']) && $fileDetails['original_name'] !== ''
			? (string) $fileDetails['original_name']
			: (string) $fileDetails['name'];
		$binFile = \App\Fields\File::sanitizeUploadFileName($fileName);
		$displayName = ltrim(basename(' ' . $binFile));
		$fileType = (string) ($fileDetails['type'] ?? 'application/octet-stream');
		$fileSize = (int) ($fileDetails['size'] ?? 0);
		$tmpName = (string) ($fileDetails['tmp_name'] ?? '');

		$uploadDir = \vtlib\Functions::initStorageFileDirectory($moduleName);
		$uniqueSuffix = $crmRecordId . '_' . time() . '_' . $binFile;
		$relativePath = $uploadDir . $uniqueSuffix;
		$absolutePath = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

		if (!move_uploaded_file($tmpName, $absolutePath)) {
			return false;
		}

		if ($replace) {
			self::deleteByRecord($crmRecordId, $role);
		}

		\App\Db\Db::getInstance()->createCommand()->insert('s_yf_record_files', [
			'crm_record_id' => $crmRecordId,
			'role' => $role,
			'storage_path' => $relativePath,
			'original_name' => $displayName,
			'mime_type' => $fileType,
			'size_bytes' => $fileSize > 0 ? $fileSize : (int) @filesize($absolutePath),
		])->execute();

		return true;
	}

	public static function deleteByRecord(int $crmRecordId, ?string $role = null): void
	{
		if ($crmRecordId <= 0) {
			return;
		}
		$rows = self::listByRecord($crmRecordId, $role);
		foreach ($rows as $row) {
			$path = self::resolveAbsolutePath((string) ($row['storage_path'] ?? ''));
			if ($path !== false && is_file($path)) {
				@unlink($path);
			}
		}
		$where = ['crm_record_id' => $crmRecordId];
		if ($role !== null) {
			$where['role'] = $role;
		}
		\App\Db\Db::getInstance()->createCommand()->delete('s_yf_record_files', $where)->execute();
	}

	/**
	 * @return list<array{id: int, orgname: string, path: string, name: string, url: string}>
	 */
	public static function getImageDetailsForRecord(int $crmRecordId, string $moduleName): array
	{
		$row = self::getByRecord($crmRecordId, self::ROLE_IMAGE);
		if ($row === null) {
			return [];
		}
		$originalName = \App\Utils\ListViewUtils::decodeHtml((string) ($row['original_name'] ?? ''));

		return [[
			'id' => (int) ($row['id'] ?? 0),
			'orgname' => $originalName,
			'path' => (string) ($row['storage_path'] ?? ''),
			'name' => $originalName,
			'url' => 'file.php?module=' . $moduleName . '&action=Image&record=' . $crmRecordId,
		]];
	}

	/**
	 * @return list<array{id: int, orgname: string, path: string, name: string, url: string}>
	 */
	public static function getImageDetailsListForRecord(int $crmRecordId, string $moduleName): array
	{
		$rows = self::listByRecord($crmRecordId, self::ROLE_IMAGE);
		$out = [];
		foreach ($rows as $row) {
			$originalName = \App\Utils\ListViewUtils::decodeHtml((string) ($row['original_name'] ?? ''));
			if ($originalName === '') {
				continue;
			}
			$out[] = [
				'id' => (int) ($row['id'] ?? 0),
				'orgname' => $originalName,
				'path' => (string) ($row['storage_path'] ?? ''),
				'name' => $originalName,
				'url' => 'file.php?module=' . $moduleName . '&action=Image&record=' . $crmRecordId,
			];
		}

		return $out;
	}
}
