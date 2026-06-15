<?php

namespace App\Modules\Documents\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Record extends \App\Modules\Base\Models\Record
{
	/** @var array<string, mixed>|null */
	private ?array $pendingUploadFile = null;

	public function setPendingUploadFile(array $fileDetails): self
	{
		$this->pendingUploadFile = $fileDetails;

		return $this;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function resolveUploadFile(string $fieldName = 'original_name'): ?array
	{
		if ($this->pendingUploadFile !== null) {
			return $this->pendingUploadFile;
		}
		if (isset($_FILES[$fieldName])) {
			return $_FILES[$fieldName];
		}
		if (isset($_FILES['filename'])) {
			return $_FILES['filename'];
		}

		return null;
	}

	public static function resolveStoragePath(?string $storagePath): string|false
	{
		return \App\Models\RecordFile::resolveAbsolutePath($storagePath);
	}

	public function getDownloadFileURL()
	{
		if ($this->get('location_type') === 'external') {
			return $this->get('external_url');
		}

		return 'index.php?module=' . $this->getModuleName() . '&action=DownloadFile&record=' . $this->getId();
	}

	public function checkFileIntegrityURL()
	{
		return "javascript:Documents_Detail_Js.checkFileIntegrity('index.php?module=" . $this->getModuleName() . "&action=CheckFileIntegrity&record=" . $this->getId() . "')";
	}

	public function checkFileIntegrity()
	{
		if ($this->get('location_type') !== 'internal') {
			return false;
		}
		$filePath = self::resolveStoragePath((string) $this->get('storage_path'));

		return $filePath !== false && is_file($filePath) && is_readable($filePath);
	}

	public function downloadFile()
	{
		if ($this->get('location_type') !== 'internal') {
			return;
		}
		$filePath = self::resolveStoragePath((string) $this->get('storage_path'));
		if ($filePath === false || !is_file($filePath)) {
			return;
		}

		$fileName = (string) ($this->get('original_name') ?: basename($filePath));
		$mimeType = (string) ($this->get('mime_type') ?: 'application/octet-stream');
		$fileSize = filesize($filePath);
		if ($fileSize === false) {
			return;
		}

		header('Content-type: ' . $mimeType);
		header('Pragma: public');
		header('Cache-Control: private');
		header('Content-Disposition: attachment; filename="' . $fileName . '"');
		header('Content-Description: PHP Generated Data');
		header('Content-Length: ' . $fileSize);
		readfile($filePath);
	}

	public function updateFileStatus($status)
	{
		\App\Db\Db::getInstance()->createCommand()->update('vtiger_notes', ['active' => (int) $status], ['notesid' => $this->getId()])->execute();
	}

	public function updateDownloadCount()
	{
		$notesId = $this->get('id');
		$downloadCount = (int) ($this->get('download_count') ?? 0) + 1;
		\App\Db\Db::getInstance()->createCommand()->update('vtiger_notes', ['download_count' => $downloadCount], ['notesid' => $notesId])->execute();
		$this->set('download_count', $downloadCount);
	}

	public function getDownloadCountUpdateUrl()
	{
		return "index.php?module=Documents&action=UpdateDownloadCount&record=" . $this->getId();
	}

	public static function getReferenceModuleByDocId($record)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$sql = 'SELECT DISTINCT vtiger_crmentity.setype 
			   FROM vtiger_crmentity INNER JOIN vtiger_senotesrel 
				   ON vtiger_senotesrel.crmid = vtiger_crmentity.crmid 
			   WHERE vtiger_crmentity.deleted = 0 
				 AND vtiger_senotesrel.notesid = ?';
		$result = $db->pquery($sql, [$record]);
		return $db->getArrayColumn($result);
	}

	public static function getFileIconByFileType($fileType)
	{
		$fileIcon = \App\Layout\Icon::getIconByFileType($fileType);
		return $fileIcon;
	}

	/**
	 * The function decide about mandatory save record
	 * @return mixed
	 */
	public function isMandatorySave()
	{
		return $_FILES ? true : false;
	}

	/**
	 * Function to save record
	 */
	public function saveToDb($relationParams = null, \App\Http\Vtiger_Request $request = null)
	{
		parent::saveToDb();
		$db = \App\Db\Db::getInstance();
		$fileNameByField = 'original_name';
		$originalName = (string) ($this->get($fileNameByField) ?? '');
		$sizeBytes = (int) ($this->get('size_bytes') ?? 0);
		$mimeType = (string) ($this->get('mime_type') ?? '');
		$downloadCount = (int) ($this->get('download_count') ?? 0);
		$locationType = $this->get('location_type');
		if ($locationType === 'E') {
			$locationType = 'external';
		} elseif ($locationType === 'I') {
			$locationType = 'internal';
		} elseif ($locationType !== 'external') {
			$locationType = 'internal';
		}

		$storagePath = null;
		$externalUrl = null;
		$active = 1;

		if ($locationType === 'internal') {
			$file = $this->resolveUploadFile($fileNameByField);
			if (is_array($file) && !empty($file['name'])) {
				$errCode = $file['error'];
				if ($errCode === 0) {
					$fileInstance = \App\Fields\File::loadFromRequest($file);
					if ($fileInstance->validate()) {
						$originalName = $file['name'];
						$originalName = \vtlib\Functions::fromHTML(preg_replace('/\s+/', '_', $originalName));
						$mimeType = $file['type'];
						$sizeBytes = (int) $file['size'];
						$originalName = ltrim(basename(' ' . $originalName));
					}
				}
			} elseif ($this->get($fileNameByField)) {
				$originalName = (string) $this->get($fileNameByField);
				$sizeBytes = (int) ($this->get('size_bytes') ?? 0);
				$mimeType = (string) ($this->get('mime_type') ?? '');
				$downloadCount = 0;
			} else {
				$mimeType = '';
				$sizeBytes = 0;
				$downloadCount = 0;
				$active = 0;
			}
		} else {
			$externalUrl = (string) ($this->get($fileNameByField) ?? '');
			if ($externalUrl !== '' && !preg_match('/^\w{1,5}:\/\/|^\w{0,3}:?\\\\\\\\/', trim($externalUrl), $match)) {
				$externalUrl = "http://$externalUrl";
			}
			$mimeType = '';
			$sizeBytes = 0;
			$downloadCount = 0;
			$originalName = '';
			$storagePath = null;
		}

		if ($locationType === 'internal') {
			$file = $this->resolveUploadFile($fileNameByField);
			if (is_array($file) && ($file['name'] ?? '') !== '' && (int) ($file['size'] ?? 0) > 0) {
				if ($request !== null) {
					$hiddenOriginal = $request->get('0_hidden');
					if ($hiddenOriginal !== null && $hiddenOriginal !== '') {
						$file['original_name'] = $hiddenOriginal;
					}
				}
				$uploadDir = \vtlib\Functions::initStorageFileDirectory('Documents');
				$binFile = \App\Fields\File::sanitizeUploadFileName(
					isset($file['original_name']) && $file['original_name'] !== '' ? $file['original_name'] : $file['name']
				);
				$displayName = ltrim(basename(' ' . $binFile));
				$relativePath = $uploadDir . $this->getId() . '_' . $binFile;
				$absolutePath = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
				if (move_uploaded_file((string) $file['tmp_name'], $absolutePath)) {
					$storagePath = $relativePath;
					$originalName = $displayName;
					$mimeType = (string) ($file['type'] ?? $mimeType);
					$sizeBytes = (int) ($file['size'] ?? 0);
					$active = 1;
				} else {
					$active = 0;
					$storagePath = null;
				}
			} elseif ($this->get('storage_path')) {
				$storagePath = (string) $this->get('storage_path');
				$active = (int) ($this->get('active') ?? 1);
			}
		}

		$db->createCommand()->update('vtiger_notes', [
			'original_name' => \App\Utils\ListViewUtils::decodeHtml($originalName),
			'external_url' => $externalUrl,
			'size_bytes' => $sizeBytes,
			'mime_type' => $mimeType,
			'location_type' => $locationType,
			'download_count' => $downloadCount,
			'storage_path' => $storagePath,
			'active' => $active,
		], ['notesid' => $this->getId()])->execute();

		$this->set('original_name', $originalName)
			->set('external_url', $externalUrl)
			->set('size_bytes', $sizeBytes)
			->set('mime_type', $mimeType)
			->set('location_type', $locationType)
			->set('download_count', $downloadCount)
			->set('storage_path', $storagePath)
			->set('active', $active);
	}

	/**
	 * Related list left column — adds “set as CV” when parent is Candidates.
	 *
	 * @param array<string, mixed> $context
	 * @return \App\Modules\Base\Models\Link[]
	 */
	public function getRelatedListLeftSideLinks(\App\Modules\Base\Models\Record $parentRecord, array $context): array
	{
		$links = parent::getRelatedListLeftSideLinks($parentRecord, $context);

		return \App\Modules\Candidates\Models\RelatedListLeftSideExtras::mergeLinks($links, $parentRecord, $this, $this->getModule(), $context);
	}
}
