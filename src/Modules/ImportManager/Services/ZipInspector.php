<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Utility for validating and extracting ZIP archives for ImportManager.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

class ZipInspector
{
	public function analyze(string $zipPath, string $targetDirectory): array
	{
		$zip = new \ZipArchive();
		if ($zip->open($zipPath) !== true) {
			throw new \RuntimeException('Nie można otworzyć pliku ZIP.');
		}

		if ($zip->numFiles !== 1) {
			$zip->close();
			throw new \RuntimeException('Archiwum ZIP musi zawierać dokładnie jeden plik CSV lub XML.');
		}

		$innerName = $zip->getNameIndex(0);
		$extension = strtolower(pathinfo($innerName, PATHINFO_EXTENSION));
		if (!in_array($extension, ['csv', 'xml'], true)) {
			$zip->close();
			throw new \RuntimeException('Archiwum ZIP musi zawierać plik CSV lub XML.');
		}

		if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
			$zip->close();
			throw new \RuntimeException('Nie można utworzyć katalogu docelowego dla importu.');
		}

		if (!$zip->extractTo($targetDirectory, [$innerName])) {
			$zip->close();
			throw new \RuntimeException('Nie udało się wypakować archiwum ZIP.');
		}

		$zip->close();

		$extractedFile = realpath($targetDirectory . DIRECTORY_SEPARATOR . $innerName);
		if ($extractedFile === false) {
			throw new \RuntimeException('Nie udało się odczytać wypakowanego pliku z archiwum.');
		}

		return [
			'path' => $extractedFile,
			'format' => $extension,
			'original' => $innerName,
		];
	}
}

