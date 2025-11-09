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

namespace App\ModuleManagement\Services;

/**
 * FileService class.
 * 
 * Service for file system operations.
 */
class FileService
{
	/**
	 * Recursively delete a file or directory.
	 * 
	 * @param string $path Path relative to ROOT_DIRECTORY
	 * @return void
	 */
	public function recurseDelete(string $path): void
	{
		$rootDir = ROOT_DIRECTORY . DIRECTORY_SEPARATOR;
		$fullPath = $rootDir . $path;
		
		if (!file_exists($fullPath)) {
			return;
		}

		$dirs = [];
		@chmod($fullPath, 0777);
		$dirs[] = $fullPath;

		if (is_dir($fullPath)) {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ($iterator as $item) {
				if ($item->isDir()) {
					$dirs[] = $rootDir . $path . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
				} else {
					unlink($rootDir . $path . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
				}
			}

			arsort($dirs);
			foreach ($dirs as $dir) {
				rmdir($dir);
			}
		} else {
			unlink($fullPath);
		}
	}

	/**
	 * Check if file access is safe for PHP inclusion.
	 * 
	 * @param string $filepath File path to check
	 * @param bool $dieOnFail Whether to throw exception on failure
	 * @return bool True if safe, false otherwise
	 * @throws \App\Exceptions\AppException If $dieOnFail is true and check fails
	 */
	public function checkFileAccessForInclusion(string $filepath, bool $dieOnFail = true): bool
	{
		$unsafeDirectories = ['storage', 'cache', 'test'];
		$realfilepath = realpath($filepath);

		if ($realfilepath === false) {
			if ($dieOnFail) {
				\App\Log::error(__METHOD__ . '(' . $filepath . ') - File does not exist');
				throw new \App\Exceptions\AppException('File does not exist: ' . $filepath);
			}
			return false;
		}

		/** Replace all \\ with \ first */
		$realfilepath = str_replace('\\\\', '\\', $realfilepath);
		$rootdirpath = str_replace('\\\\', '\\', ROOT_DIRECTORY . DIRECTORY_SEPARATOR);

		/** Replace all \ with / now */
		$realfilepath = str_replace('\\', '/', $realfilepath);
		$rootdirpath = str_replace('\\', '/', $rootdirpath);

		$relativeFilePath = str_replace($rootdirpath, '', $realfilepath);
		$filePathParts = explode('/', $relativeFilePath);

		if (stripos($realfilepath, $rootdirpath) !== 0 || in_array($filePathParts[0], $unsafeDirectories)) {
			if ($dieOnFail) {
				\App\Log::error(__METHOD__ . '(' . $filepath . ') - Sorry! Attempt to access restricted file. realfilepath: ' . print_r($realfilepath, true));
				throw new \App\Exceptions\AppException('Sorry! Attempt to access restricted file.');
			}
			return false;
		}

		return true;
	}

	/**
	 * Create module files based on template skeleton.
	 *
	 * @param \vtlib\Module $module
	 * @param \vtlib\Field $entityField
	 * @return void
	 */
	public function createModuleFiles(\vtlib\Module $module, \vtlib\Field $entityField): void
	{
		$moduleName = $module->name;
		$targetPath = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $moduleName;

		if (is_dir($targetPath)) {
			return;
		}

		$templatePath = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'vtlib' . DIRECTORY_SEPARATOR . 'ModuleDir' . DIRECTORY_SEPARATOR . 'BaseModule' . DIRECTORY_SEPARATOR;
		$flags = \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS;
		$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($templatePath, $flags), \RecursiveIteratorIterator::SELF_FIRST);

		foreach ($objects as $name => $object) {
			$relativeTarget = str_replace($templatePath, '', $name);
			$relativeTarget = str_replace('_ModuleName_', $moduleName, $relativeTarget);
			$destination = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . $relativeTarget;

			if ($object->isDir()) {
				if (!is_dir($destination)) {
					mkdir($destination, 0777, true);
				}
			} else {
				$fileContent = file_get_contents($name);
				$replaceVars = [
					'<ModuleName>' => $moduleName,
					'<ModuleLabel>' => $module->label,
					'<modulename>' => strtolower($moduleName),
					'<entityfieldlabel>' => $entityField->label,
					'<entitycolumn>' => $entityField->column,
					'<entityfieldname>' => $entityField->name,
					'_ModuleName_' => $moduleName,
				];
				foreach ($replaceVars as $search => $replace) {
					$fileContent = str_replace($search, addslashes($replace), $fileContent);
				}
				file_put_contents($destination, $fileContent);
			}
		}

		$languages = \App\Modules\Users\Models\Module::getLanguagesList();
		$sourceLangFile = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'en_us' . DIRECTORY_SEPARATOR . $moduleName . '.php';
		if (file_exists($sourceLangFile)) {
			foreach ($languages as $langKey => $language) {
				if ($langKey === 'en_us') {
					continue;
				}
				$destDir = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . $langKey;
				if (!is_dir($destDir)) {
					mkdir($destDir, 0777, true);
				}
				$destLangFile = $destDir . DIRECTORY_SEPARATOR . $moduleName . '.php';
				if (!file_exists($destLangFile)) {
					copy($sourceLangFile, $destLangFile);
				}
			}
		}

		$legacyModuleDir = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $moduleName;
		$legacyModuleFile = $legacyModuleDir . DIRECTORY_SEPARATOR . $moduleName . '.php';
		$psrModuleDir = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $moduleName;
		if (!is_dir($psrModuleDir)) {
			mkdir($psrModuleDir, 0777, true);
		}
		$moduleFile = $psrModuleDir . DIRECTORY_SEPARATOR . $moduleName . '.php';
		if (file_exists($legacyModuleFile)) {
			rename($legacyModuleFile, $moduleFile);
			@rmdir($legacyModuleDir);
		}
		if (file_exists($moduleFile)) {
			$content = file_get_contents($moduleFile);
			$content = str_replace("include_once 'modules/Vtiger/CRMEntity.php';\n", '', $content);
			$content = str_replace("use App\\CRMEntity as Vtiger_CRMEntity;\n", '', $content);
			$content = preg_replace('/^<\?php\s*/', "<?php\n\nnamespace App\\Modules\\{$moduleName};\n\n", $content);
			$content = str_replace('PearDatabase::getInstance()', '\\App\\Database\\PearDatabase::getInstance()', $content);
			file_put_contents($moduleFile, $content);
		}
	}
}

