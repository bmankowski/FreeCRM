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

namespace vtlib;

/**
 * Language Export adapter class.
 * 
 * Backward compatibility adapter for language package export.
 * 
 * @deprecated This is a minimal stub implementation
 */
class LanguageExport
{
	/**
	 * Get all languages.
	 * 
	 * @return array Array of languages with prefix as key and label as value
	 */
	public static function getAll(): array
	{
		return \App\Runtime\Vtiger_Language_Handler::getAllLanguages();
	}

	/**
	 * Export language package to ZIP file.
	 * 
	 * @param string $lang Language prefix (e.g., 'en_us')
	 * @param string $todir Target directory (unused)
	 * @param string $zipfilename ZIP filename
	 * @param bool $directDownload Whether to force download
	 * @return void
	 */
	public function export(string $lang, string $todir = '', string $zipfilename = '', bool $directDownload = false): void
	{
		$languageDir = "languages/$lang";
		if (!is_dir($languageDir)) {
			throw new \Exception("Language directory not found: $languageDir");
		}

		// Generate ZIP filename if not provided
		if (empty($zipfilename)) {
			$zipfilename = $lang . '_' . date('Y-m-d-Hi') . '.zip';
		}

		$tempDir = sys_get_temp_dir();
		$zipfilepath = "$tempDir/$zipfilename";

		// Create ZIP file
		$zip = new \ZipArchive();
		if ($zip->open($zipfilepath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
			throw new \Exception("Cannot create ZIP file: $zipfilepath");
		}

		// Add all language files recursively
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($languageDir, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item) {
			if ($item->isFile() && $item->getExtension() === 'php') {
				$filePath = $item->getRealPath();
				$relativePath = str_replace('\\', '/', substr($filePath, strlen(realpath($languageDir)) + 1));
				$zipPath = "languages/$lang/$relativePath";
				$zip->addFile($filePath, $zipPath);
			}
		}

		$zip->close();

		// Handle download or save
		if ($directDownload) {
			$this->forceDownload($zipfilepath);
			if (file_exists($zipfilepath)) {
				unlink($zipfilepath);
			}
		} elseif ($todir) {
			copy($zipfilepath, "$todir/$zipfilename");
			if (file_exists($zipfilepath)) {
				unlink($zipfilepath);
			}
		}
	}

	/**
	 * Force download ZIP file.
	 * 
	 * @param string $zipfilepath Full path to ZIP file
	 * @return void
	 */
	private function forceDownload(string $zipfilepath): void
	{
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private", false);
		header("Content-Type: application/zip");
		header("Content-Disposition: attachment; filename='" . basename($zipfilepath) . "';");
		$disk_file_size = filesize($zipfilepath);
		$zipfilesize = $disk_file_size + ($disk_file_size % 1024);
		header("Content-Length: " . $zipfilesize);
		$fileContent = fread(fopen($zipfilepath, "rb"), $zipfilesize);
		echo $fileContent;
	}
}


