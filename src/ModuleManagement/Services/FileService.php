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
				$itemPath = $item->getRealPath();
				if ($item->isDir()) {
					$dirs[] = $itemPath;
				} else {
					@chmod($itemPath, 0777);
					unlink($itemPath);
				}
			}

			arsort($dirs);
			foreach ($dirs as $dir) {
				@chmod($dir, 0777);
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
				\App\Log\Log::error(__METHOD__ . '(' . $filepath . ') - File does not exist');
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
				\App\Log\Log::error(__METHOD__ . '(' . $filepath . ') - Sorry! Attempt to access restricted file. realfilepath: ' . print_r($realfilepath, true));
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
	 * @param object $entityField object with ->name, ->label, ->column properties
	 * @return void
	 */
	public function createModuleFiles(\vtlib\Module $module, object $entityField): void
	{
		$moduleName = $module->name;
		$psrModuleFile = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Modules'
			. DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . $moduleName . '.php';

		if (is_file($psrModuleFile)) {
			return;
		}

		$templatePath = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'vtlib' . DIRECTORY_SEPARATOR . 'ModuleDir' . DIRECTORY_SEPARATOR . 'BaseModule' . DIRECTORY_SEPARATOR;
		if (!is_dir($templatePath)) {
			$this->createMinimalModuleFiles($module, $entityField);
			return;
		}

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
		// Copy JSON language files (YetiForce compatible format)
		$sourceLangFile = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'en_us' . DIRECTORY_SEPARATOR . $moduleName . '.json';
		if (file_exists($sourceLangFile)) {
			foreach ($languages as $langKey => $language) {
				if ($langKey === 'en_us') {
					continue;
				}
				$destDir = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . $langKey;
				if (!is_dir($destDir)) {
					mkdir($destDir, 0777, true);
				}
				$destLangFile = $destDir . DIRECTORY_SEPARATOR . $moduleName . '.json';
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
			$content = str_replace('PearDatabase::getInstance()', '\App\\Database\\PearDatabase::getInstance()', $content);
			file_put_contents($moduleFile, $content);
		}
	}

	/**
	 * Create a minimal PSR-4 CRMEntity class when vtlib template skeleton is unavailable.
	 */
	private function createMinimalModuleFiles(\vtlib\Module $module, object $entityField): void
	{
		$moduleName = $module->name;
		$lcasemodname = strtolower($moduleName);
		$basetable = $module->basetable ?: "vtiger_{$lcasemodname}";
		$basetableid = $module->basetableid ?: $lcasemodname . 'id';
		$customtable = $module->customtable ?: $basetable . 'cf';
		$fieldName = (string) ($entityField->name ?? 'name');
		$fieldLabel = (string) ($entityField->label ?? $fieldName);
		$fieldLabelEscaped = addslashes($fieldLabel);

		$psrModuleDir = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Modules'
			. DIRECTORY_SEPARATOR . $moduleName;
		if (!is_dir($psrModuleDir) && !mkdir($psrModuleDir, 0775, true) && !is_dir($psrModuleDir)) {
			throw new \App\Exceptions\AppException('Cannot create module directory: ' . $psrModuleDir);
		}

		$moduleFile = $psrModuleDir . DIRECTORY_SEPARATOR . $moduleName . '.php';
		$content = <<<PHP
<?php

namespace App\\Modules\\{$moduleName};

class {$moduleName} extends \\App\\Core\\CRMEntity
{
	public \$table_name = '{$basetable}';
	public \$table_index = '{$basetableid}';

	public \$customFieldTable = ['{$customtable}', '{$basetableid}'];

	public \$tab_name = ['vtiger_crmentity', '{$basetable}', '{$customtable}'];

	public \$tab_name_index = [
		'vtiger_crmentity' => 'crmid',
		'{$basetable}' => '{$basetableid}',
		'{$customtable}' => '{$basetableid}',
	];

	public \$list_fields_name = [
		'{$fieldLabelEscaped}' => '{$fieldName}',
		'Assigned To' => 'assigned_user_id',
	];

	public \$search_fields = [
		'{$fieldLabelEscaped}' => ['{$lcasemodname}', '{$fieldName}'],
	];

	public \$search_fields_name = [
		'{$fieldLabelEscaped}' => '{$fieldName}',
	];

	public \$popup_fields = ['{$fieldName}'];
	public \$def_basicsearch_col = '{$fieldName}';
	public \$def_detailview_recname = '{$fieldName}';
	public \$mandatory_fields = ['{$fieldName}', 'assigned_user_id'];
}

PHP;
		if (file_put_contents($moduleFile, $content) === false) {
			throw new \App\Exceptions\AppException('Cannot write module file: ' . $moduleFile);
		}
	}
}

