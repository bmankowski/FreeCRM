<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Handles upload + staging metadata for ImportManager wizard.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

class UploadService
{
	private ConfigProvider $config;
	private BatchRepository $batches;
	private ZipInspector $zipInspector;

	public function __construct(
		?ConfigProvider $config = null,
		?BatchRepository $batchRepository = null,
		?ZipInspector $zipInspector = null
	) {
		$this->config = $config ?? new ConfigProvider();
		$this->batches = $batchRepository ?? new BatchRepository();
		$this->zipInspector = $zipInspector ?? new ZipInspector();
	}

	/**
	 * @param array $fileInfo $_FILES entry
	 * @param array $payload request data (module, delimiter, etc.)
	 */
	public function handle(array $fileInfo, array $payload, int $userId): array
	{
		$this->validateFileArray($fileInfo);
		$this->validateTargetModule($payload['target_module'] ?? '');

		$extension = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
		$allowedExtensions = $this->config->getAllowedExtensions();

		if (!in_array($extension, $allowedExtensions, true)) {
			throw new \RuntimeException('Niedozwolony format pliku importu.');
		}

		if ((int) $fileInfo['size'] > $this->config->getMaxUploadSizeBytes()) {
			throw new \RuntimeException('Przekroczono maksymalny rozmiar pliku importu (' . $this->config->get('fileLimits.maxUploadSizeMb', 10) . ' MB).');
		}

		$sanitizedName = \App\Fields\File::sanitizeUploadFileName($fileInfo['name']);
		$baseStorage = ROOT_DIRECTORY . '/storage/imports';
		if (!is_dir($baseStorage) && !mkdir($baseStorage, 0775, true) && !is_dir($baseStorage)) {
			throw new \RuntimeException('Nie można utworzyć katalogu storage/imports.');
		}

		$tmpDir = $baseStorage . '/tmp_' . uniqid('', true);
		if (!mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
			throw new \RuntimeException('Nie można utworzyć katalogu tymczasowego dla importu.');
		}

		$tmpFilePath = $tmpDir . DIRECTORY_SEPARATOR . $sanitizedName;
		if (!move_uploaded_file($fileInfo['tmp_name'], $tmpFilePath)) {
			if (!copy($fileInfo['tmp_name'], $tmpFilePath)) {
				throw new \RuntimeException('Nie udało się zapisać przesłanego pliku.');
			}
			@unlink($fileInfo['tmp_name']);
		}

		$relativeTmpDir = $this->toRelativePath($tmpDir);
		$relativeTmpFile = $this->toRelativePath($tmpFilePath);

		$batchId = $this->batches->create([
			'module' => $payload['target_module'],
			'created_by' => $userId,
			'status' => 'uploaded',
			'duplicate_strategy' => $payload['duplicate_strategy'] ?? 'skip',
			'file_name' => $sanitizedName,
			'file_path' => $relativeTmpFile,
			'storage_path' => $relativeTmpDir,
			'file_size' => (int) $fileInfo['size'],
			'file_hash' => hash_file('sha256', $tmpFilePath),
			'format' => $extension,
			'delimiter' => $payload['delimiter'] ?? null,
			'enclosure' => $payload['enclosure'] ?? '"',
			'encoding' => $payload['encoding'] ?? 'UTF-8',
			'xpath' => $payload['xpath'] ?? null,
			'options' => \App\Utils\Json::encode([
				'original_name' => $fileInfo['name'],
				'zip_inner' => null,
			]),
			'cleanup_after' => date('Y-m-d H:i:s', strtotime(sprintf('+%d days', $this->config->getRetentionDays()))),
		]);

		$finalDir = $baseStorage . '/' . $batchId;
		$this->moveDirectory($tmpDir, $finalDir);

		$finalFilePath = $finalDir . DIRECTORY_SEPARATOR . $sanitizedName;
		$relativeFinalDir = $this->toRelativePath($finalDir);
		$relativeFinalFile = $this->toRelativePath($finalFilePath);

		$primaryPath = $finalFilePath;
		$finalFormat = $extension;
		$innerFileRelative = null;

		if ($extension === 'zip') {
			$analysis = $this->zipInspector->analyze($finalFilePath, $finalDir);
			$primaryPath = $analysis['path'];
			$finalFormat = $analysis['format'];
			$innerFileRelative = $this->toRelativePath($analysis['path']);
		}

		$updatePayload = [
			'storage_path' => $relativeFinalDir,
			'file_path' => $innerFileRelative ?? $relativeFinalFile,
			'format' => $finalFormat,
		];

		if ($innerFileRelative) {
			$options = [
				'original_name' => $fileInfo['name'],
				'zip_inner' => $innerFileRelative,
			];
			$updatePayload['options'] = \App\Utils\Json::encode($options);
		}

		$this->batches->update($batchId, $updatePayload);

		return [
			'batchId' => $batchId,
			'absolutePath' => $primaryPath,
			'format' => $finalFormat,
			'file' => [
				'name' => $sanitizedName,
				'size' => (int) $fileInfo['size'],
			],
		];
	}

	private function validateFileArray(array $fileInfo): void
	{
		if (!isset($fileInfo['tmp_name']) || !is_uploaded_file($fileInfo['tmp_name'])) {
			throw new \RuntimeException('Nieprawidłowy plik importu.');
		}

		if (!empty($fileInfo['error'])) {
			throw new \RuntimeException('Wystąpił błąd podczas przesyłania pliku (kod: ' . $fileInfo['error'] . ').');
		}
	}

	private function validateTargetModule(string $moduleName): void
	{
		if ($moduleName === '') {
			throw new \RuntimeException('Nie wybrano modułu docelowego.');
		}

		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		if (!$moduleModel) {
			throw new \RuntimeException('Wybrany moduł nie istnieje.');
		}
	}

	private function moveDirectory(string $source, string $destination): void
	{
		if (@rename($source, $destination)) {
			return;
		}

		$directoryIterator = new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS);
		$iterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);

		foreach ($iterator as $item) {
			$targetPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
			if ($item->isDir()) {
				if (!is_dir($targetPath) && !mkdir($targetPath, 0775, true) && !is_dir($targetPath)) {
					throw new \RuntimeException('Nie można przenieść katalogu importu.');
				}
			} else {
				if (!is_dir(dirname($targetPath)) && !mkdir(dirname($targetPath), 0775, true) && !is_dir(dirname($targetPath))) {
					throw new \RuntimeException('Nie można utworzyć katalogu docelowego importu.');
				}
				if (!rename((string) $item, $targetPath)) {
					throw new \RuntimeException('Nie można przenieść pliku importu.');
				}
			}
		}
		$this->deleteDirectory($source);
	}

	private function deleteDirectory(string $path): void
	{
		$iterator = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
		foreach (new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST) as $item) {
			if ($item->isDir()) {
				@rmdir((string) $item);
			} else {
				@unlink((string) $item);
			}
		}
		@rmdir($path);
	}

	private function toRelativePath(string $absolutePath): string
	{
		$root = rtrim(ROOT_DIRECTORY, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		return ltrim(str_replace($root, '', $absolutePath), DIRECTORY_SEPARATOR);
	}
}

