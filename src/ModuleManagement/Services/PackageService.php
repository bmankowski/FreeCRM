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

use App\ModuleManagement\Models;
use App\ModuleManagement\Events;
use ZipArchive;

/**
 * PackageService class.
 * 
 * Service for managing module package import, export, and update operations.
 */
class PackageService
{
	/** @var \App\Db\Db Database instance */
	private $db;
	
	/** @var Events\Dispatcher Event dispatcher */
	private $eventDispatcher;
	
	/** @var string Temporary directory for package operations */
	private $tempDir;
	
	/** @var \SimpleXMLElement Parsed manifest.xml */
	private $modulexml;
	
	/** @var array Module fields cache for import */
	private $modulefields_cache = [];
	
	/** @var string Error text */
	private $errorText = '';
	
	/** @var string Package type */
	private $packageType = '';
	
	/** @var array Package parameters */
	private $parameters = [];
	
	/** @var string Manifest file path */
	private $manifestFilePath;
	
	/** @var resource Manifest file handle */
	private $manifestFileHandle;

	/**
	 * Constructor.
	 * 
	 * @param \App\Db\Db $db
	 * @param Events\Dispatcher $eventDispatcher
	 * @param string $tempDir
	 */
	public function __construct(\App\Db\Db $db, Events\Dispatcher $eventDispatcher, string $tempDir = 'cache/vtlib')
	{
		$this->db = $db;
		$this->eventDispatcher = $eventDispatcher;
		$this->tempDir = $tempDir;
		
		// Ensure temp directory exists
		if (!is_dir($this->tempDir)) {
			mkdir($this->tempDir, 0755, true);
		}
	}

	/**
	 * Check if ZIP file is a valid package.
	 * 
	 * @param string $zipfile
	 * @return bool
	 */
	public function checkZip(string $zipfile): bool
	{
		if (!file_exists($zipfile)) {
			$this->errorText = "ZIP file not found: $zipfile";
			return false;
		}

		$zip = new ZipArchive();
		if ($zip->open($zipfile) !== true) {
			$this->errorText = "Cannot open ZIP file: $zipfile";
			return false;
		}

		$manifestxml_found = false;
		$languagefile_found = false;
		$layoutfile_found = false;
		$updatefile_found = false;
		$extensionfile_found = false;
		$moduleVersionFound = false;
		$modulename = null;
		$language_modulename = null;

		// Check for manifest.xml
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$filename = $zip->getNameIndex($i);
			
			if (preg_match('/manifest\.xml$/', $filename)) {
				$manifestxml_found = true;
				$this->__parseManifestFile($zip, $filename);
				$modulename = (string) $this->modulexml->name;
				$isModuleBundle = (string) $this->modulexml->modulebundle;

				if ($isModuleBundle === 'true' && !empty($this->modulexml->dependencies->vtiger_version)) {
					$languagefile_found = true;
					break;
				}

				// Check package type
				if ($this->isLanguageType()) {
					$languagefile_found = true;
					break;
				} elseif ($this->isLayoutType()) {
					$layoutfile_found = true;
					break;
				} elseif ($this->isExtensionType()) {
					$extensionfile_found = true;
					break;
				} elseif ($this->isUpdateType()) {
					$updatefile_found = true;
					break;
				}
			}

			// Check for language files (JSON format - YetiForce compatible)
			$defaultLanguage = \App\Core\AppConfig::main('default_language');
			// Normalize language code: pl_pl -> pl-PL, en_us -> en-US (for YetiForce compatibility)
			$defaultLanguageNormalized = str_replace('_', '-', $defaultLanguage);
			$defaultLanguageParts = explode('-', $defaultLanguageNormalized);
			if (count($defaultLanguageParts) == 2) {
				$defaultLanguageNormalized = strtolower($defaultLanguageParts[0]) . '-' . strtoupper($defaultLanguageParts[1]);
			}
			
			// Check JSON format: languages/pl_pl/ModuleName.json (FreeCRM format)
			$pattern = '/languages\/' . preg_quote($defaultLanguage, '/') . '\/([^\/]+)\.json$/';
			if (preg_match($pattern, $filename, $matches)) {
				$language_modulename = $matches[1];
			}
			
			// Check JSON format: languages/pl-PL/ModuleName.json (YetiForce format)
			$patternJson = '/languages\/' . preg_quote($defaultLanguageNormalized, '/') . '\/([^\/]+)\.json$/';
			if (preg_match($patternJson, $filename, $matches)) {
				$language_modulename = $matches[1];
			}
			
			// Also check alternative formats (with dash instead of underscore)
			$altPattern = '/languages\/' . preg_quote(str_replace('_', '-', $defaultLanguage), '/') . '\/([^\/]+)\.json$/i';
			if (preg_match($altPattern, $filename, $matches)) {
				$language_modulename = $matches[1];
			}

			// Check Settings modules JSON format: languages/pl_pl/Settings/ModuleName.json
			$settingsPattern = '/languages\/' . preg_quote($defaultLanguage, '/') . '\/Settings\/([^\/]+)\.json$/';
			if (preg_match($settingsPattern, $filename, $matches)) {
				$language_modulename = $matches[1];
			}
			
			// Check Settings modules JSON format: languages/pl-PL/Settings/ModuleName.json (YetiForce format)
			$settingsPatternJson = '/languages\/' . preg_quote($defaultLanguageNormalized, '/') . '\/Settings\/([^\/]+)\.json$/';
			if (preg_match($settingsPatternJson, $filename, $matches)) {
				$language_modulename = $matches[1];
			}
		}

		// Verify module language file
		if (!empty($language_modulename) && $language_modulename == $modulename) {
			$languagefile_found = true;
		} elseif (!$updatefile_found && !$layoutfile_found && !$languagefile_found && !empty($modulename)) {
			$_errorText = \App\Runtime\Vtiger_Language_Handler::translate('LBL_ERROR_NO_DEFAULT_LANGUAGE', 'Settings:ModuleManager');
			$_errorText = str_replace('__DEFAULTLANGUAGE__', \App\Core\AppConfig::main('default_language'), $_errorText);
			$this->errorText = $_errorText;
		}

		// Check version compatibility
		if (!empty($this->modulexml->dependencies->vtiger_version)) {
			$moduleVersion = (string) $this->modulexml->dependencies->vtiger_version;
			if (\App\Core\Version::check($moduleVersion) === true) {
				$moduleVersionFound = true;
			} else {
				$_errorText = \App\Runtime\Vtiger_Language_Handler::translate('LBL_ERROR_VERSION', 'Settings:ModuleManager');
				$_errorText = str_replace('__MODULEVERSION__', $moduleVersion, $_errorText);
				$_errorText = str_replace('__CRMVERSION__', \App\Core\Version::get(), $_errorText);
				$this->errorText = $_errorText;
			}
		}

		$validzip = false;
		if ($manifestxml_found && $languagefile_found && $moduleVersionFound) {
			$validzip = true;
		}
		if ($manifestxml_found && $layoutfile_found && $moduleVersionFound) {
			$validzip = true;
		}
		if ($manifestxml_found && $languagefile_found && $extensionfile_found && $moduleVersionFound) {
			$validzip = true;
		}
		if ($manifestxml_found && $updatefile_found && $moduleVersionFound) {
			$validzip = true;
		}

		// Extract license if valid
		if ($validzip && !empty($this->modulexml->license)) {
			if (!empty($this->modulexml->license->inline)) {
				// License is inline in manifest
			} elseif (!empty($this->modulexml->license->file)) {
				$licensefile = (string) $this->modulexml->license->file;
				if ($zip->locateName($licensefile) !== false) {
					$licenseContent = $zip->getFromName($licensefile);
					// Store license content if needed
				}
			}
		}

		$zip->close();
		return $validzip;
	}

	/**
	 * Parse manifest file from ZIP.
	 * 
	 * @param ZipArchive $zip
	 * @param string $manifestPath
	 * @return void
	 */
	private function __parseManifestFile(ZipArchive $zip, string $manifestPath): void
	{
		$manifestContent = $zip->getFromName($manifestPath);
		if ($manifestContent === false) {
			throw new \Exception("Cannot extract manifest.xml from ZIP");
		}
		
		$manifestfile = $this->__getManifestFilePath();
		file_put_contents($manifestfile, $manifestContent);
		$this->modulexml = simplexml_load_file($manifestfile);
		unlink($manifestfile);
	}

	/**
	 * Get temporary manifest file path.
	 * 
	 * @return string
	 */
	private function __getManifestFilePath(): string
	{
		if (empty($this->manifestFilePath)) {
			$this->manifestFilePath = $this->tempDir . '/manifest-' . time() . '.xml';
		}
		return $this->manifestFilePath;
	}

	/**
	 * Get module name from ZIP file.
	 * 
	 * @param string $zipfile
	 * @return string|null
	 */
	public function getModuleNameFromZip(string $zipfile): ?string
	{
		if (!$this->checkZip($zipfile)) {
			return null;
		}
		return (string) $this->modulexml->name;
	}

	/**
	 * Get package type.
	 * 
	 * @return string|null
	 */
	public function type(): ?string
	{
		if (!empty($this->modulexml) && !empty($this->modulexml->type)) {
			return (string) $this->modulexml->type;
		}
		return null;
	}

	/**
	 * Check if language package.
	 * 
	 * @param string|null $zipfile
	 * @return bool
	 */
	public function isLanguageType(?string $zipfile = null): bool
	{
		if (!empty($zipfile)) {
			if (!$this->checkZip($zipfile)) {
				return false;
			}
		}
		$packagetype = $this->type();
		if ($packagetype) {
			$lcasetype = strtolower($packagetype);
			if ($lcasetype == 'language' || $lcasetype == 'layout') {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if extension package.
	 * 
	 * @param string|null $zipfile
	 * @return bool
	 */
	public function isExtensionType(?string $zipfile = null): bool
	{
		if (!empty($zipfile)) {
			if (!$this->checkZip($zipfile)) {
				return false;
			}
		}
		$packagetype = $this->type();
		if ($packagetype) {
			$lcasetype = strtolower($packagetype);
			if ($lcasetype == 'extension') {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if update package.
	 * 
	 * @param string|null $zipfile
	 * @return bool
	 */
	public function isUpdateType(?string $zipfile = null): bool
	{
		if (!empty($zipfile)) {
			if (!$this->checkZip($zipfile)) {
				return false;
			}
		}
		$packagetype = $this->type();
		if ($packagetype) {
			$lcasetype = strtolower($packagetype);
			if ($lcasetype == 'update') {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if layout package.
	 * 
	 * @param string|null $zipfile
	 * @return bool
	 */
	public function isLayoutType(?string $zipfile = null): bool
	{
		if (!empty($zipfile)) {
			if (!$this->checkZip($zipfile)) {
				return false;
			}
		}
		$packagetype = $this->type();
		if ($packagetype) {
			$lcasetype = strtolower($packagetype);
			if ($lcasetype == 'layout') {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if module bundle.
	 * 
	 * @param string|null $zipfile
	 * @return bool
	 */
	public function isModuleBundle(?string $zipfile = null): bool
	{
		if (!empty($zipfile)) {
			if (!$this->checkZip($zipfile)) {
				return false;
			}
		}
		return (bool) $this->modulexml->modulebundle;
	}

	/**
	 * Check if package is in FreeCRM format.
	 * 
	 * @param string|null $zipfile
	 * @return bool
	 */
	public function isFreeCRMFormat(?string $zipfile = null): bool
	{
		if (!empty($zipfile)) {
			if (!$this->checkZip($zipfile)) {
				return false;
			}
		}
		if (!empty($this->modulexml->packageFormat)) {
			return (string) $this->modulexml->packageFormat === 'FreeCRM';
		}
		return false;
	}

	/**
	 * Get error text.
	 * 
	 * @return string
	 */
	public function getErrorText(): string
	{
		return $this->errorText;
	}

	/**
	 * XPath evaluation on manifest.
	 * 
	 * @param string $path
	 * @return array
	 */
	public function xpath(string $path): array
	{
		return $this->modulexml->xpath($path);
	}

	/**
	 * Get XPath value.
	 * 
	 * @param string $path
	 * @return mixed
	 */
	public function xpath_value(string $path)
	{
		$xpathres = $this->xpath($path);
		foreach ($xpathres as $pathkey => $pathvalue) {
			if ($pathkey == $path) {
				return $pathvalue;
			}
		}
		return false;
	}

	/**
	 * Get license text.
	 * 
	 * @return string|null
	 */
	public function getLicense(): ?string
	{
		if (!empty($this->modulexml->license->inline)) {
			return (string) $this->modulexml->license->inline;
		}
		return null;
	}

	/**
	 * Get parameters.
	 * 
	 * @return array
	 */
	public function getParameters(): array
	{
		$parameters = [];
		if (!empty($this->modulexml->parameters)) {
			foreach ($this->modulexml->parameters->parameter as $parameter) {
				$parameters[] = (string) $parameter;
			}
		}
		return $parameters;
	}

	/**
	 * Get version.
	 * 
	 * @return string|null
	 */
	public function getVersion(): ?string
	{
		if (!empty($this->modulexml->version)) {
			return (string) $this->modulexml->version;
		}
		return null;
	}

	/**
	 * Get dependent vtiger version.
	 * 
	 * @return string|null
	 */
	public function getDependentVtigerVersion(): ?string
	{
		if (!empty($this->modulexml->dependencies->vtiger_version)) {
			return (string) $this->modulexml->dependencies->vtiger_version;
		}
		return null;
	}

	/**
	 * Get dependent max vtiger version.
	 * 
	 * @return string|null
	 */
	public function getDependentMaxVtigerVersion(): ?string
	{
		if (!empty($this->modulexml->dependencies->vtiger_max_version)) {
			return (string) $this->modulexml->dependencies->vtiger_max_version;
		}
		return null;
	}

	/**
	 * Get temporary file path.
	 * 
	 * @param string|null $filepath
	 * @return string
	 */
	public function getTemporaryFilePath(?string $filepath = null): string
	{
		if ($filepath) {
			return 'cache/' . $filepath;
		}
		return 'cache/';
	}

	/**
	 * Import module from ZIP file.
	 * 
	 * @param string $zipfile
	 * @param bool $overwrite
	 * @return void
	 * @throws \Exception
	 */
	public function import(string $zipfile, bool $overwrite = false): void
	{
		$module = $this->getModuleNameFromZip($zipfile);
		if ($module == null) {
			throw new \Exception("Cannot get module name from ZIP file");
		}

		// Handle module bundles
		if ($this->isModuleBundle()) {
			$buildModuleArray = [];
			$installSequenceArray = [];
			$moduleList = (array) $this->modulexml->modulelist;
			foreach ($moduleList as $moduleInfos) {
				foreach ($moduleInfos as $moduleInfo) {
					$moduleInfo = (array) $moduleInfo;
					$buildModuleArray[] = $moduleInfo;
					$installSequenceArray[] = $moduleInfo['install_sequence'];
				}
			}
			sort($installSequenceArray);
			
			// Extract bundle to temp directory
			$zip = new ZipArchive();
			$zip->open($zipfile);
			$tempPath = $this->getTemporaryFilePath();
			$zip->extractTo($tempPath);
			$zip->close();
			
			// Import each module in sequence
			foreach ($installSequenceArray as $sequence) {
				foreach ($buildModuleArray as $moduleInfo) {
					if ($moduleInfo['install_sequence'] == $sequence) {
						$this->import($this->getTemporaryFilePath($moduleInfo['filepath']), $overwrite);
					}
				}
			}
			return;
		}

		// Single module import
		$module = $this->initImport($zipfile, $overwrite);
		if ($module) {
			$this->import_Module();
		}
	}

	/**
	 * Initialize import - extract files from ZIP.
	 * 
	 * @param string $zipfile
	 * @param bool $overwrite
	 * @return string|null Module name
	 */
	public function initImport(string $zipfile, bool $overwrite = true): ?string
	{
		$module = $this->getModuleNameFromZip($zipfile);
		if ($module == null) {
			return null;
		}

		$zip = new ZipArchive();
		if ($zip->open($zipfile) !== true) {
			throw new \Exception("Cannot open ZIP file: $zipfile");
		}

		$defaultLayout = \App\Runtime\CRM_Viewer::getDefaultLayoutName();
		
		// Extract files with path mapping
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$filename = $zip->getNameIndex($i);
			if ($filename === false) {
				continue;
			}

			// Skip directories
			if (substr($filename, -1) === '/') {
				continue;
			}

			$targetPath = null;
			
			// Map paths
			if (strpos($filename, 'templates/') === 0) {
				$targetPath = "layouts/$defaultLayout/modules/$module/" . substr($filename, 10);
			} elseif (strpos($filename, "modules/$module/") === 0) {
				$targetPath = $filename;
			} elseif (strpos($filename, 'cron/') === 0) {
				$targetPath = "cron/modules/$module/" . substr($filename, 5);
			} elseif (strpos($filename, 'config/') === 0) {
				$targetPath = 'config/modules/' . substr($filename, 7);
			} elseif (strpos($filename, 'languages/') === 0) {
				$targetPath = $filename;
			} elseif (strpos($filename, 'settings/actions/') === 0) {
				$targetPath = "modules/Settings/$module/actions/" . substr($filename, 17);
			} elseif (strpos($filename, 'settings/views/') === 0) {
				$targetPath = "modules/Settings/$module/views/" . substr($filename, 15);
			} elseif (strpos($filename, 'settings/models/') === 0) {
				$targetPath = "modules/Settings/$module/models/" . substr($filename, 16);
			} elseif (strpos($filename, 'settings/templates/') === 0) {
				$targetPath = "layouts/$defaultLayout/modules/Settings/$module/" . substr($filename, 19);
			} elseif (strpos($filename, 'settings/connectors/') === 0) {
				$targetPath = "modules/Settings/$module/connectors/" . substr($filename, 20);
			} elseif (strpos($filename, 'settings/libraries/') === 0) {
				$targetPath = "modules/Settings/$module/libraries/" . substr($filename, 19);
			} elseif ($filename === "$module.png") {
				$targetPath = "layouts/$defaultLayout/skins/images/$module.png";
			} elseif (strpos($filename, 'updates/') === 0) {
				$targetPath = 'cache/updates/' . substr($filename, 8);
			} elseif (strpos($filename, 'layouts/') === 0) {
				$targetPath = $filename;
			}

			if ($targetPath && ($overwrite || !file_exists($targetPath))) {
				$dir = dirname($targetPath);
				if (!is_dir($dir)) {
					mkdir($dir, 0755, true);
				}
				file_put_contents($targetPath, $zip->getFromIndex($i));
			}
		}

		$zip->close();
		return $module;
	}

	/**
	 * Import module from manifest.
	 * 
	 * @return void
	 * @throws \Exception
	 */
	public function import_Module(): void
	{
		$tabname = (string) $this->modulexml->name;
		$tabLabel = (string) $this->modulexml->label;
		$tabVersion = (string) $this->modulexml->version;

		$isextension = false;
		$moduleType = 0;
		if (!empty($this->modulexml->type)) {
			$this->packageType = strtolower((string) $this->modulexml->type);
			if ($this->packageType == 'extension' || $this->packageType == 'language') {
				$isextension = true;
			}
			if ($this->packageType == 'inventory') {
				$moduleType = 1;
			}
		}

		$vtigerMinVersion = !empty($this->modulexml->dependencies->vtiger_version) ? (string) $this->modulexml->dependencies->vtiger_version : false;
		$vtigerMaxVersion = !empty($this->modulexml->dependencies->vtiger_max_version) ? (string) $this->modulexml->dependencies->vtiger_max_version : false;

		// Create Module model
		$module = new Models\Module(
			null, // id - will be set by create
			$tabname,
			$tabLabel,
			$tabVersion ? (int) $tabVersion : 0,
			$vtigerMinVersion ?: false,
			$vtigerMaxVersion ?: false,
			0, // presence - enabled
			0, // ownedby
			false, // tabsequence
			false, // parent
			1, // customized
			!$isextension, // isentitytype
			false, // entityidcolumn
			false, // entityidfield
			false, // basetable
			false, // basetableid
			false, // customtable
			false, // grouptable
			$moduleType, // type
			null // tableName
		);

		if ($this->packageType != 'update') {
			$moduleService = \App\ModuleManagement\ServiceLocator::getModuleService();
			$moduleId = $moduleService->create($module);
			$module = $moduleService->getInstance($moduleId);

			$this->import_Tables($this->modulexml);
			$this->import_Blocks($this->modulexml, $module);
			$this->importInventory();
			$this->import_CustomViews($this->modulexml, $module);
			$this->import_SharingAccess($this->modulexml, $module);
			$this->import_Events($this->modulexml, $module);
			$this->import_Actions($this->modulexml, $module);
			$this->import_RelatedLists($this->modulexml, $module);
			$this->import_CustomLinks($this->modulexml, $module);
			$this->import_CronTasks($this->modulexml);
			
			$this->eventDispatcher->fire($module->getName(), 'module.postinstall');
		} else {
			$this->import_update($this->modulexml);
		}
	}

	/**
	 * Import tables from manifest.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @return void
	 */
	private function import_Tables(\SimpleXMLElement $modulenode): void
	{
		if (empty($modulenode->tables) || empty($modulenode->tables->table)) {
			return;
		}

		$adb = \App\Database\PearDatabase::getInstance();
		$adb->query('SET FOREIGN_KEY_CHECKS = 0;');

		foreach ($modulenode->tables->table as $tablenode) {
			$tableName = (string) $tablenode->name;
			$sql = (string) $tablenode->sql;

			if ($this->isCreateSql($sql)) {
				if (!$this->db->isTableExists($tableName)) {
					$this->executeQuery($sql);
				}
			} else {
				if (!$this->isDestructiveSql($sql)) {
					$this->executeQuery($sql, true);
				}
			}
		}

		$adb->query('SET FOREIGN_KEY_CHECKS = 1;');
	}

	/**
	 * Check if SQL is CREATE TABLE.
	 * 
	 * @param string $sql
	 * @return bool
	 */
	private function isCreateSql(string $sql): bool
	{
		return (bool) preg_match('/(CREATE TABLE)/i', $sql);
	}

	/**
	 * Check if SQL is destructive.
	 * 
	 * @param string $sql
	 * @return bool
	 */
	private function isDestructiveSql(string $sql): bool
	{
		return (bool) preg_match('/(DROP TABLE)|(DROP COLUMN)|(DELETE FROM)/i', $sql);
	}

	/**
	 * Execute SQL query.
	 * 
	 * @param string $sqlquery
	 * @param bool $suppressDie
	 * @return void
	 */
	private function executeQuery(string $sqlquery, bool $suppressDie = false): void
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$old_dieOnError = $adb->dieOnError;

		if ($suppressDie) {
			$adb->dieOnError = false;
		}

		$adb->pquery($sqlquery, []);

		$adb->dieOnError = $old_dieOnError;
	}

	/**
	 * Import blocks from manifest.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @param Models\Module $module
	 * @return void
	 */
	private function import_Blocks(\SimpleXMLElement $modulenode, Models\Module $module): void
	{
		if (empty($modulenode->blocks) || empty($modulenode->blocks->block)) {
			return;
		}

		$blockService = \App\ModuleManagement\ServiceLocator::getBlockService();
		
		foreach ($modulenode->blocks->block as $blocknode) {
			$block = $this->import_Block($modulenode, $module, $blocknode);
			$this->import_Fields($blocknode, $block, $module);
		}
	}

	/**
	 * Import single block.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @param Models\Module $module
	 * @param \SimpleXMLElement $blocknode
	 * @return Models\Block
	 */
	private function import_Block(\SimpleXMLElement $modulenode, Models\Module $module, \SimpleXMLElement $blocknode): Models\Block
	{
		$blocklabel = (string) $blocknode->label;
		
		$sequence = isset($blocknode->sequence) ? (int) $blocknode->sequence : false;
		$showtitle = isset($blocknode->show_title) ? (int) $blocknode->show_title : 1;
		$visible = isset($blocknode->visible) ? (int) $blocknode->visible : 1;
		$increateview = isset($blocknode->create_view) ? (int) $blocknode->create_view : 1;
		$ineditview = isset($blocknode->edit_view) ? (int) $blocknode->edit_view : 1;
		$indetailview = isset($blocknode->detail_view) ? (int) $blocknode->detail_view : 1;
		$display_status = isset($blocknode->display_status) ? (int) $blocknode->display_status : 0;
		$iscustom = isset($blocknode->iscustom) ? (int) $blocknode->iscustom : 1;

		$block = new Models\Block(
			null, // id
			$blocklabel,
			$sequence,
			$showtitle,
			$visible,
			$increateview,
			$ineditview,
			$indetailview,
			$display_status,
			$iscustom,
			null // module
		);

		$blockService = \App\ModuleManagement\ServiceLocator::getBlockService();
		$blockId = $blockService->create($module->getId(), $block);
		
		// Get the created block with ID
		$allBlocks = $blockService->getAllForModule($module->getId());
		foreach ($allBlocks as $b) {
			if ($b->getLabel() === $blocklabel) {
				return $b;
			}
		}
		
		return $block;
	}

	/**
	 * Import fields from manifest.
	 * 
	 * @param \SimpleXMLElement $blocknode
	 * @param Models\Block $block
	 * @param Models\Module $module
	 * @return void
	 */
	private function import_Fields(\SimpleXMLElement $blocknode, Models\Block $block, Models\Module $module): void
	{
		if (empty($blocknode->fields) || empty($blocknode->fields->field)) {
			return;
		}

		foreach ($blocknode->fields->field as $fieldnode) {
			$this->import_Field($blocknode, $block, $module, $fieldnode);
		}
	}

	/**
	 * Import single field.
	 * 
	 * @param \SimpleXMLElement $blocknode
	 * @param Models\Block $block
	 * @param Models\Module $module
	 * @param \SimpleXMLElement $fieldnode
	 * @return void
	 */
	private function import_Field(\SimpleXMLElement $blocknode, Models\Block $block, Models\Module $module, \SimpleXMLElement $fieldnode): void
	{
		$fieldService = \App\ModuleManagement\ServiceLocator::getFieldService();
		
		$field = new Models\Field(
			null, // id
			(string) $fieldnode->fieldname,
			$module->getId(),
			(string) $fieldnode->fieldlabel,
			(string) $fieldnode->tablename,
			(string) $fieldnode->columnname,
			isset($fieldnode->columntype) ? (string) $fieldnode->columntype : false,
			isset($fieldnode->helpinfo) ? (string) $fieldnode->helpinfo : '',
			isset($fieldnode->summaryfield) ? (int) $fieldnode->summaryfield : 0,
			false, // header_field
			0, // maxlengthtext
			0, // maxwidthcolumn
			isset($fieldnode->masseditable) ? (int) $fieldnode->masseditable : 1,
			(int) $fieldnode->uitype,
			(string) $fieldnode->typeofdata,
			isset($fieldnode->displaytype) ? (int) $fieldnode->displaytype : 1,
			(int) $fieldnode->generatedtype,
			(int) $fieldnode->readonly,
			(int) $fieldnode->presence,
			isset($fieldnode->defaultvalue) ? (string) $fieldnode->defaultvalue : '',
			isset($fieldnode->maximumlength) ? (int) $fieldnode->maximumlength : 100,
			isset($fieldnode->sequence) ? (int) $fieldnode->sequence : false,
			isset($fieldnode->quickcreate) ? (int) $fieldnode->quickcreate : 1,
			isset($fieldnode->quickcreatesequence) ? (int) $fieldnode->quickcreatesequence : false,
			isset($fieldnode->info_type) ? (string) $fieldnode->info_type : 'BAS',
			$block,
			isset($fieldnode->fieldparams) ? (string) $fieldnode->fieldparams : ''
		);

		$fieldId = $fieldService->create($module->getId(), $block->getId(), $field);
		
		// Cache field for later use
		$this->modulefields_cache[$module->getName()][$field->getName()] = $field;

		// Set entity identifier if specified
		if (!empty($fieldnode->entityidentifier)) {
			$moduleService = \App\ModuleManagement\ServiceLocator::getModuleService();
			$moduleService->setEntityIdentifier($module->getId(), $fieldId);
		}

		// Set picklist values
		if (!empty($fieldnode->picklistvalues) && !empty($fieldnode->picklistvalues->picklistvalue)) {
			$picklistvalues = [];
			foreach ($fieldnode->picklistvalues->picklistvalue as $picklistvaluenode) {
				$picklistvalues[] = (string) $picklistvaluenode;
			}
			$fieldService->setPicklistValues($fieldId, $picklistvalues);
		}

		// Set related modules
		if (!empty($fieldnode->relatedmodules) && !empty($fieldnode->relatedmodules->relatedmodule)) {
			$relatedmodules = [];
			foreach ($fieldnode->relatedmodules->relatedmodule as $relatedmodulenode) {
				$relatedmodules[] = (string) $relatedmodulenode;
			}
			$fieldService->setRelatedModules($fieldId, $relatedmodules);
		}
	}

	/**
	 * Import related lists.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @param Models\Module $module
	 * @return void
	 */
	private function import_RelatedLists(\SimpleXMLElement $modulenode, Models\Module $module): void
	{
		$relationService = \App\ModuleManagement\ServiceLocator::getRelationService();
		$moduleService = \App\ModuleManagement\ServiceLocator::getModuleService();

		if (!empty($modulenode->relatedlists) && !empty($modulenode->relatedlists->relatedlist)) {
			foreach ($modulenode->relatedlists->relatedlist as $relatedlistnode) {
				$targetModule = $moduleService->getInstance((string) $relatedlistnode->relatedmodule);
				if ($targetModule) {
					$label = (string) $relatedlistnode->label;
					$actions = [];
					if (!empty($relatedlistnode->actions) && !empty($relatedlistnode->actions->action)) {
						foreach ($relatedlistnode->actions->action as $actionnode) {
							$actions[] = (string) $actionnode;
						}
					}
					$functionName = isset($relatedlistnode->function) ? (string) $relatedlistnode->function : 'getRelatedList';
					$relationService->setRelatedList($module->getId(), $targetModule->getId(), $label, $actions, $functionName);
				}
			}
		}

		if (!empty($modulenode->inrelatedlists) && !empty($modulenode->inrelatedlists->inrelatedlist)) {
			foreach ($modulenode->inrelatedlists->inrelatedlist as $inRelatedListNode) {
				$sourceModule = $moduleService->getInstance((string) $inRelatedListNode->inrelatedmodule);
				if ($sourceModule) {
					$label = (string) $inRelatedListNode->label;
					$actions = [];
					if (!empty($inRelatedListNode->actions) && !empty($inRelatedListNode->actions->action)) {
						foreach ($inRelatedListNode->actions->action as $actionnode) {
							$actions[] = (string) $actionnode;
						}
					}
					$functionName = isset($inRelatedListNode->function) ? (string) $inRelatedListNode->function : 'getRelatedList';
					$relationService->setRelatedList($sourceModule->getId(), $module->getId(), $label, $actions, $functionName);
				}
			}
		}
	}

	/**
	 * Import custom views (filters).
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @param Models\Module $module
	 * @return void
	 */
	private function import_CustomViews(\SimpleXMLElement $modulenode, Models\Module $module): void
	{
		if (empty($modulenode->customviews) || empty($modulenode->customviews->customview)) {
			return;
		}
		$filterService = \App\ModuleManagement\ServiceLocator::getFilterService();
		foreach ($modulenode->customviews->customview as $customviewnode) {
			$filterService->importFromXML($module, $customviewnode, $this->modulefields_cache);
		}
	}

	/**
	 * Import sharing access.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @param Models\Module $module
	 * @return void
	 */
	private function import_SharingAccess(\SimpleXMLElement $modulenode, Models\Module $module): void
	{
		if (empty($modulenode->sharingaccess)) {
			return;
		}
		$accessService = \App\ModuleManagement\ServiceLocator::getAccessService();
		if (!empty($modulenode->sharingaccess->default)) {
			foreach ($modulenode->sharingaccess->default as $defaultnode) {
				$accessService->setDefaultSharing($module->getId(), (string) $defaultnode);
			}
		}
	}

	/**
	 * Import events.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @param Models\Module $module
	 * @return void
	 */
	private function import_Events(\SimpleXMLElement $modulenode, Models\Module $module): void
	{
		if (empty($modulenode->eventHandlers) || empty($modulenode->eventHandlers->event)) {
			return;
		}
		$moduleId = \App\Utils\ModuleUtils::getModuleId($module->getName());
		foreach ($modulenode->eventHandlers->event as $eventNode) {
			\App\Events\EventHandler::registerHandler(
				(string) $eventNode->eventName,
				(string) $eventNode->className,
				(string) $eventNode->includeModules,
				(string) $eventNode->excludeModules,
				(int) $eventNode->priority,
				(int) $eventNode->isActive,
				$moduleId
			);
		}
	}

	/**
	 * Import actions.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @param Models\Module $module
	 * @return void
	 */
	private function import_Actions(\SimpleXMLElement $modulenode, Models\Module $module): void
	{
		if (empty($modulenode->actions) || empty($modulenode->actions->action)) {
			return;
		}
		$accessService = \App\ModuleManagement\ServiceLocator::getAccessService();
		foreach ($modulenode->actions->action as $actionnode) {
			$actionstatus = (string) $actionnode->status;
			if ($actionstatus === 'enabled') {
				$accessService->updateTool($module->getId(), (string) $actionnode->name, true);
			} else {
				$accessService->updateTool($module->getId(), (string) $actionnode->name, false);
			}
		}
	}

	/**
	 * Import custom links.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @param Models\Module $module
	 * @return void
	 */
	private function import_CustomLinks(\SimpleXMLElement $modulenode, Models\Module $module): void
	{
		if (empty($modulenode->customlinks) || empty($modulenode->customlinks->customlink)) {
			return;
		}
		$linkService = \App\ModuleManagement\ServiceLocator::getLinkService();
		foreach ($modulenode->customlinks->customlink as $customlinknode) {
			$handlerInfo = null;
			if (!empty($customlinknode->handler_path)) {
				$handlerInfo = [
					'path' => (string) $customlinknode->handler_path,
					'class' => (string) $customlinknode->handler_class,
					'method' => (string) $customlinknode->handler
				];
			}
			$linkService->addLink(
				$module->getId(),
				(string) $customlinknode->linktype,
				(string) $customlinknode->linklabel,
				(string) $customlinknode->linkurl,
				(string) $customlinknode->linkicon,
				(int) $customlinknode->sequence,
				$handlerInfo
			);
		}
	}

	/**
	 * Import cron tasks.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @return void
	 */
	private function import_CronTasks(\SimpleXMLElement $modulenode): void
	{
		if (empty($modulenode->crons) || empty($modulenode->crons->cron)) {
			return;
		}
		$cronService = \App\ModuleManagement\ServiceLocator::getCronService();
		foreach ($modulenode->crons->cron as $cronTask) {
			$status = empty($cronTask->status) ? \App\ModuleManagement\Services\CronService::STATUS_DISABLED : \App\ModuleManagement\Services\CronService::STATUS_ENABLED;
			$sequence = empty($cronTask->sequence) ? $cronService->nextSequence() : (int) $cronTask->sequence;
			$cronService->register(
				(string) $cronTask->name,
				(string) $cronTask->handler,
				(string) $cronTask->frequency,
				(string) $modulenode->name,
				$status,
				$sequence,
				(string) $cronTask->description
			);
		}
	}

	/**
	 * Import inventory fields.
	 * 
	 * @return void
	 */
	private function importInventory(): void
	{
		// Inventory import logic - delegate to existing implementation
		// This is complex and may need to be handled separately
	}

	/**
	 * Import update package.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @return void
	 */
	private function import_update(\SimpleXMLElement $modulenode): void
	{
		// Update package import logic
		// This is handled by PackageUpdate class
	}

	/**
	 * Create vtlib Module instance for compatibility.
	 * 
	 * @param Models\Module $module
	 * @return \vtlib\Module
	 */
	private function createVtlibModuleInstance(Models\Module $module): \vtlib\Module
	{
		$vtlibModule = new \vtlib\Module();
		$vtlibModule->id = $module->getId();
		$vtlibModule->name = $module->getName();
		$vtlibModule->label = $module->getLabel();
		$vtlibModule->version = $module->getVersion();
		$vtlibModule->presence = $module->getPresence();
		$vtlibModule->ownedby = $module->getOwnedby();
		$vtlibModule->tabsequence = $module->getTabsequence();
		$vtlibModule->parent = $module->getParent();
		$vtlibModule->customized = $module->getCustomized();
		$vtlibModule->isentitytype = $module->getIsentitytype();
		$vtlibModule->entityidcolumn = $module->getEntityidcolumn();
		$vtlibModule->entityidfield = $module->getEntityidfield();
		$vtlibModule->basetable = $module->getBasetable();
		$vtlibModule->basetableid = $module->getBasetableid();
		$vtlibModule->customtable = $module->getCustomtable();
		$vtlibModule->grouptable = $module->getGrouptable();
		$vtlibModule->type = $module->getType();
		return $vtlibModule;
	}

	/**
	 * Export module to ZIP file.
	 * 
	 * @param Models\Module $module
	 * @param string $todir
	 * @param string $zipfilename
	 * @param bool $directDownload
	 * @return void
	 * @throws \Exception
	 */
	public function export(Models\Module $module, string $todir = '', string $zipfilename = '', bool $directDownload = false): void
	{
		$this->__initExport($module);
		$this->export_Module($module);
		$this->__finishExport();

		// Generate ZIP filename if not provided
		if (empty($zipfilename)) {
			$zipfilename = $module->getName() . '_' . date('Y-m-d-Hi') . '_' . $module->getVersion() . '.zip';
		}
		$zipfilename = "$this->tempDir/$zipfilename";

		// Create ZIP file
		$zip = new ZipArchive();
		if ($zip->open($zipfilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
			throw new \Exception("Cannot create ZIP file: $zipfilename");
		}

		// Add manifest file
		$manifestPath = $this->__getManifestFilePath();
		$zip->addFile($manifestPath, 'manifest.xml');

		// Copy module directory - check both old and new locations
		$modulePath = "modules/{$module->getName()}";
		$modulePathNew = "src/Modules/{$module->getName()}";
		if (is_dir($modulePathNew)) {
			$this->copyDirectoryToZip($zip, $modulePathNew, "modules/{$module->getName()}");
		} elseif (is_dir($modulePath)) {
			$this->copyDirectoryToZip($zip, $modulePath, "modules/{$module->getName()}");
		}

		// Copy Settings/module directory - check both old and new locations
		$settingsModulePath = "modules/Settings/{$module->getName()}";
		$settingsModulePathNew = "src/Modules/Settings/{$module->getName()}";
		if (is_dir($settingsModulePathNew)) {
			$this->copyDirectoryToZip($zip, $settingsModulePathNew, "settings/");
		} elseif (is_dir($settingsModulePath)) {
			$this->copyDirectoryToZip($zip, $settingsModulePath, "settings/");
		}

		// Copy cron files
		if (is_dir("cron/modules/{$module->getName()}")) {
			$this->copyDirectoryToZip($zip, "cron/modules/{$module->getName()}", "cron/");
		}

		// Copy module templates files - check both old and new locations
		$defaultLayout = \App\Runtime\CRM_Viewer::getDefaultLayoutName();
		$templatesPath = "layouts/$defaultLayout/modules/{$module->getName()}";
		$templatesPathNew = "public/layouts/$defaultLayout/modules/{$module->getName()}";
		// Copy from new location if exists
		if (is_dir($templatesPathNew)) {
			$this->copyDirectoryToZip($zip, $templatesPathNew, "templates/");
		}
		// Also copy from old location if exists (may have additional templates)
		if (is_dir($templatesPath)) {
			$this->copyDirectoryToZip($zip, $templatesPath, "templates/");
		}

		// Copy Settings module templates files - check both old and new locations
		$settingsTemplatesPath = "layouts/$defaultLayout/modules/Settings/{$module->getName()}";
		$settingsTemplatesPathNew = "public/layouts/$defaultLayout/modules/Settings/{$module->getName()}";
		// Copy from new location if exists
		if (is_dir($settingsTemplatesPathNew)) {
			$this->copyDirectoryToZip($zip, $settingsTemplatesPathNew, "settings/templates/");
		}
		// Also copy from old location if exists (may have additional templates)
		if (is_dir($settingsTemplatesPath)) {
			$this->copyDirectoryToZip($zip, $settingsTemplatesPath, "settings/templates/");
		}

		// Copy language files
		$this->__copyLanguageFiles($zip, $module->getName());

		// Copy module icon - check both old and new locations
		$iconPath = "layouts/$defaultLayout/skins/images/{$module->getName()}.png";
		$iconPathNew = "public/layouts/$defaultLayout/skins/images/{$module->getName()}.png";
		if (file_exists($iconPathNew)) {
			$zip->addFile($iconPathNew, "{$module->getName()}.png");
		} elseif (file_exists($iconPath)) {
			$zip->addFile($iconPath, "{$module->getName()}.png");
		}

		$zip->close();

		// Handle download or save
		if ($directDownload) {
			$this->forceDownload($zipfilename);
			unlink($zipfilename);
		} elseif ($todir) {
			copy($zipfilename, "$todir/" . basename($zipfilename));
			unlink($zipfilename);
		}

		$this->__cleanupExport();
	}

	/**
	 * Initialize export.
	 * 
	 * @param Models\Module $module
	 * @return void
	 */
	private function __initExport(Models\Module $module): void
	{
		$this->manifestFilePath = $this->__getManifestFilePath();
		$this->manifestFileHandle = fopen($this->manifestFilePath, 'w');
		$this->__write("<?xml version='1.0'?>\n");
	}

	/**
	 * Finish export.
	 * 
	 * @return void
	 */
	private function __finishExport(): void
	{
		if (!empty($this->manifestFileHandle)) {
			fclose($this->manifestFileHandle);
			$this->manifestFileHandle = null;
		}
	}

	/**
	 * Cleanup export.
	 * 
	 * @return void
	 */
	private function __cleanupExport(): void
	{
		if (!empty($this->manifestFilePath) && file_exists($this->manifestFilePath)) {
			unlink($this->manifestFilePath);
			$this->manifestFilePath = null;
		}
	}

	/**
	 * Write to manifest file.
	 * 
	 * @param string $value
	 * @return void
	 */
	private function __write(string $value): void
	{
		fwrite($this->manifestFileHandle, $value);
	}

	/**
	 * Open XML node.
	 * 
	 * @param string $node
	 * @param string $delimiter
	 * @return void
	 */
	private function openNode(string $node, string $delimiter = PHP_EOL): void
	{
		$this->__write("<$node>$delimiter");
	}

	/**
	 * Close XML node.
	 * 
	 * @param string $node
	 * @param string $delimiter
	 * @return void
	 */
	private function closeNode(string $node, string $delimiter = PHP_EOL): void
	{
		$this->__write("</$node>$delimiter");
	}

	/**
	 * Output XML node with value.
	 * 
	 * @param mixed $value
	 * @param string $node
	 * @return void
	 */
	private function outputNode($value, string $node = ''): void
	{
		if ($node != '') {
			$this->openNode($node, '');
		}
		$this->__write(htmlspecialchars((string) $value, ENT_XML1, 'UTF-8'));
		if ($node != '') {
			$this->closeNode($node);
		}
	}

	/**
	 * Export module to manifest.
	 * 
	 * @param Models\Module $module
	 * @return void
	 */
	private function export_Module(Models\Module $module): void
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$tabInfo = (new \App\Db\Query())
			->from('vtiger_tab')
			->where(['tabid' => $module->getId()])
			->one();

		$tabname = $tabInfo['name'];
		$tablabel = $tabInfo['tablabel'];
		$tabVersion = $tabInfo['version'] ?? false;

		$this->openNode('module');
		$this->outputNode(date('Y-m-d H:i:s'), 'exporttime');
		$this->outputNode($tabname, 'name');
		$this->outputNode($tablabel, 'label');

		if (!$module->getIsentitytype()) {
			$type = 'extension';
		} elseif ($tabInfo['type'] == 1) {
			$type = 'inventory';
		} else {
			$type = 'entity';
		}
		$this->outputNode($type, 'type');

		if ($tabVersion) {
			$this->outputNode($tabVersion, 'version');
		}

		// Mark as FreeCRM format
		$this->outputNode('FreeCRM', 'packageFormat');

		// Export dependencies
		$this->export_Dependencies($module);

		// Export tables
		$this->export_Tables($module);

		// Export blocks
		$this->export_Blocks($module);

		// Export filters
		$this->export_CustomViews($module);

		// Export inventory fields
		if ($tabInfo['type'] == 1) {
			$this->exportInventory($module);
		}

		// Export sharing access
		$this->export_SharingAccess($module);

		// Export events
		$this->export_Events($module);

		// Export actions
		$this->export_Actions($module);

		// Export related lists
		$this->export_RelatedLists($module);

		// Export custom links
		$this->export_CustomLinks($module);

		// Export cron tasks
		$this->export_CronTasks($module);

		$this->closeNode('module');
	}

	/**
	 * Export dependencies.
	 * 
	 * @param Models\Module $module
	 * @return void
	 */
	private function export_Dependencies(Models\Module $module): void
	{
		$minVersion = \App\Core\Version::get();
		$maxVersion = false;

		$tabInfo = (new \App\Db\Query())
			->from('vtiger_tab_info')
			->where(['tabid' => $module->getId()])
			->all();

		foreach ($tabInfo as $info) {
			if ($info['prefname'] == 'vtiger_min_version') {
				$minVersion = $info['prefvalue'];
			}
			if ($info['prefname'] == 'vtiger_max_version') {
				$maxVersion = $info['prefvalue'];
			}
		}

		$this->openNode('dependencies');
		$this->outputNode($minVersion, 'vtiger_version');
		if ($maxVersion !== false) {
			$this->outputNode($maxVersion, 'vtiger_max_version');
		}
		$this->closeNode('dependencies');
	}

	/**
	 * Export tables.
	 * 
	 * @param Models\Module $module
	 * @return void
	 */
	private function export_Tables(Models\Module $module): void
	{
		$this->openNode('tables');

		if ($module->getIsentitytype()) {
			$focus = \App\Core\CRMEntity::getInstance($module->getName());
			\App\Utils\VtlibUtils::setupModuleVars($module->getName(), $focus);
			$tables = $focus->tab_name;
			if (($key = array_search('vtiger_crmentity', $tables)) !== false) {
				unset($tables[$key]);
			}
			foreach ($tables as $table) {
				$this->openNode('table');
				$this->outputNode($table, 'name');
				$adb = \App\Database\PearDatabase::getInstance();
				$result = $adb->query("SHOW CREATE TABLE $table");
				$createTable = $adb->fetch_array($result);
				$sql = \App\Utils\ListViewUtils::decodeHtml($createTable['Create Table']);
				$this->__write('<![CDATA[' . $sql . ']]>');
				$this->closeNode('sql');
				$this->closeNode('table');
			}
		}
		$this->closeNode('tables');
	}

	/**
	 * Export blocks.
	 * 
	 * @param Models\Module $module
	 * @return void
	 */
	private function export_Blocks(Models\Module $module): void
	{
		$blockService = \App\ModuleManagement\ServiceLocator::getBlockService();
		$blocks = $blockService->getAllForModule($module->getId());

		if (empty($blocks)) {
			return;
		}

		$this->openNode('blocks');
		foreach ($blocks as $block) {
			$this->openNode('block');
			$this->outputNode($block->getLabel(), 'label');
			$this->outputNode($block->getSequence(), 'sequence');
			$this->outputNode($block->getShowtitle(), 'show_title');
			$this->outputNode($block->getVisible(), 'visible');
			$this->outputNode($block->getIncreateview(), 'create_view');
			$this->outputNode($block->getIneditview(), 'edit_view');
			$this->outputNode($block->getIndetailview(), 'detail_view');
			$this->outputNode($block->getDisplay_status(), 'display_status');
			$this->outputNode($block->getIscustom(), 'iscustom');

			// Export fields
			$this->export_Fields($module, $block->getId());
			$this->closeNode('block');
		}
		$this->closeNode('blocks');
	}

	/**
	 * Export fields.
	 * 
	 * @param Models\Module $module
	 * @param int $blockId
	 * @return void
	 */
	private function export_Fields(Models\Module $module, int $blockId): void
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$fieldresult = $adb->pquery("SELECT * FROM vtiger_field WHERE tabid=? && block=?", [$module->getId(), $blockId]);
		$fieldcount = $adb->num_rows($fieldresult);

		if (empty($fieldcount)) {
			return;
		}

		$entityresult = $adb->pquery("SELECT * FROM vtiger_entityname WHERE tabid=?", [$module->getId()]);
		$entity_fieldname = $adb->num_rows($entityresult) > 0 ? $adb->query_result($entityresult, 0, 'fieldname') : null;

		$this->openNode('fields');
		for ($index = 0; $index < $fieldcount; ++$index) {
			$this->openNode('field');
			$fieldresultrow = $adb->fetchByAssoc($fieldresult);

			$fieldname = $fieldresultrow['fieldname'];
			$uitype = $fieldresultrow['uitype'];
			$fieldid = $fieldresultrow['fieldid'];

			// Get column type from information schema
			$info_schema = $adb->pquery("SELECT column_name, column_type FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = SCHEMA() && table_name = ? && column_name = ?", [$fieldresultrow['tablename'], $fieldresultrow['columnname']]);
			$info_schemarow = $adb->fetchByAssoc($info_schema);
			$columntype = ($info_schemarow && isset($info_schemarow['column_type'])) ? $info_schemarow['column_type'] : '';

			$this->outputNode($fieldname, 'fieldname');
			$this->outputNode($uitype, 'uitype');
			$this->outputNode($fieldresultrow['columnname'], 'columnname');
			$this->outputNode($columntype, 'columntype');
			$this->outputNode($fieldresultrow['tablename'], 'tablename');
			$this->outputNode($fieldresultrow['generatedtype'], 'generatedtype');
			$this->outputNode($fieldresultrow['fieldlabel'], 'fieldlabel');
			$this->outputNode($fieldresultrow['readonly'], 'readonly');
			$this->outputNode($fieldresultrow['presence'], 'presence');
			$this->outputNode($fieldresultrow['defaultvalue'], 'defaultvalue');
			$this->outputNode($fieldresultrow['sequence'], 'sequence');
			$this->outputNode($fieldresultrow['maximumlength'], 'maximumlength');
			$this->outputNode($fieldresultrow['typeofdata'], 'typeofdata');
			$this->outputNode($fieldresultrow['quickcreate'], 'quickcreate');
			$this->outputNode($fieldresultrow['quickcreatesequence'], 'quickcreatesequence');
			$this->outputNode($fieldresultrow['displaytype'], 'displaytype');
			$this->outputNode($fieldresultrow['info_type'], 'info_type');
			$this->outputNode($fieldresultrow['fieldparams'], 'fieldparams');
			$this->outputNode($fieldresultrow['helpinfo'], 'helpinfo');

			if (isset($fieldresultrow['masseditable'])) {
				$this->outputNode($fieldresultrow['masseditable'], 'masseditable');
			}
			if (isset($fieldresultrow['summaryfield'])) {
				$this->outputNode($fieldresultrow['summaryfield'], 'summaryfield');
			}

			// Export entity identifier
			if ($entity_fieldname && $fieldname == $entity_fieldname) {
				$this->openNode('entityidentifier');
				$this->outputNode($adb->query_result($entityresult, 0, 'entityidfield'), 'entityidfield');
				$this->outputNode($adb->query_result($entityresult, 0, 'entityidcolumn'), 'entityidcolumn');
				$this->closeNode('entityidentifier');
			}

			// Export picklist values
			if (in_array($uitype, ['15', '16', '111', '33', '55'])) {
				if ($uitype == '16') {
					$picklistvalues = \App\Fields\Picklist::getPickListValues($fieldname);
				} else {
					$picklistvalues = \App\Utils\VtlibUtils::getPicklistValuesAccessibleToAll($fieldname);
				}
				$this->openNode('picklistvalues');
				foreach ($picklistvalues as $picklistvalue) {
					$this->outputNode($picklistvalue, 'picklistvalue');
				}
				$this->closeNode('picklistvalues');
			}

			// Export related modules
			if ($uitype == '10') {
				$relatedmodres = $adb->pquery("SELECT * FROM vtiger_fieldmodulerel WHERE fieldid=?", [$fieldid]);
				$relatedmodcount = $adb->num_rows($relatedmodres);
				if ($relatedmodcount) {
					$this->openNode('relatedmodules');
					for ($relmodidx = 0; $relmodidx < $relatedmodcount; ++$relmodidx) {
						$this->outputNode($adb->query_result($relatedmodres, $relmodidx, 'relmodule'), 'relatedmodule');
					}
					$this->closeNode('relatedmodules');
				}
			}

			$this->closeNode('field');
		}
		$this->closeNode('fields');
	}

	/**
	 * Export custom views (filters).
	 * 
	 * @param Models\Module $module
	 * @return void
	 */
	private function export_CustomViews(Models\Module $module): void
	{
		$filterService = \App\ModuleManagement\ServiceLocator::getFilterService();
		$filterService->exportToXML($module, $this->manifestFileHandle);
	}

	/**
	 * Export sharing access.
	 * 
	 * @param Models\Module $module
	 * @return void
	 */
	private function export_SharingAccess(Models\Module $module): void
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$deforgshare = $adb->pquery("SELECT * FROM vtiger_def_org_share WHERE tabid=?", [$module->getId()]);
		$deforgshareCount = $adb->num_rows($deforgshare);

		if (empty($deforgshareCount)) {
			return;
		}

		$this->openNode('sharingaccess');
		for ($index = 0; $index < $deforgshareCount; ++$index) {
			$permission = $adb->query_result($deforgshare, $index, 'permission');
			$permissiontext = '';
			if ($permission == '0') {
				$permissiontext = 'public_readonly';
			} elseif ($permission == '1') {
				$permissiontext = 'public_readwrite';
			} elseif ($permission == '2') {
				$permissiontext = 'public_readwritedelete';
			} elseif ($permission == '3') {
				$permissiontext = 'private';
			}
			$this->outputNode($permissiontext, 'default');
		}
		$this->closeNode('sharingaccess');
	}

	/**
	 * Export events.
	 * 
	 * @param Models\Module $module
	 * @return void
	 */
	private function export_Events(Models\Module $module): void
	{
		// Events export - delegate to existing implementation if available
		// Currently not implemented in vtlib
	}

	/**
	 * Export actions.
	 * 
	 * @param Models\Module $module
	 * @return void
	 */
	private function export_Actions(Models\Module $module): void
	{
		if (!$module->getIsentitytype()) {
			return;
		}

		$adb = \App\Database\PearDatabase::getInstance();
		$result = $adb->pquery('SELECT distinct(actionname) FROM vtiger_profile2utility, vtiger_actionmapping WHERE vtiger_profile2utility.activityid=vtiger_actionmapping.actionid and tabid=?', [$module->getId()]);

		if ($adb->num_rows($result)) {
			$this->openNode('actions');
			while ($resultrow = $adb->fetch_array($result)) {
				$this->openNode('action');
				$this->__write('<![CDATA[' . $resultrow['actionname'] . ']]>');
				$this->closeNode('name');
				$this->outputNode('enabled', 'status');
				$this->closeNode('action');
			}
			$this->closeNode('actions');
		}
	}

	/**
	 * Export related lists.
	 * 
	 * @param Models\Module $module
	 * @return void
	 */
	private function export_RelatedLists(Models\Module $module): void
	{
		if (!$module->getIsentitytype()) {
			return;
		}

		$adb = \App\Database\PearDatabase::getInstance();
		$result = $adb->pquery("SELECT * FROM vtiger_relatedlists WHERE tabid = ?", [$module->getId()]);
		if ($adb->num_rows($result)) {
			$this->openNode('relatedlists');
			$countResult = $adb->num_rows($result);
			for ($index = 0; $index < $countResult; ++$index) {
				$row = $adb->fetch_array($result);
				$this->openNode('relatedlist');

				$targetModule = \App\ModuleManagement\ServiceLocator::getModuleService()->getInstance($row['related_tabid']);
				if ($targetModule) {
					$this->outputNode($targetModule->getName(), 'relatedmodule');
					$this->outputNode($row['label'], 'label');
					$this->outputNode($row['name'], 'function');
					if (!empty($row['actions'])) {
						$this->openNode('actions');
						$actions = explode(',', $row['actions']);
						foreach ($actions as $action) {
							$this->outputNode(trim($action), 'action');
						}
						$this->closeNode('actions');
					}
				}
				$this->closeNode('relatedlist');
			}
			$this->closeNode('relatedlists');
		}
	}

	/**
	 * Export custom links.
	 * 
	 * @param Models\Module $module
	 * @return void
	 */
	private function export_CustomLinks(Models\Module $module): void
	{
		$linkService = \App\ModuleManagement\ServiceLocator::getLinkService();
		$linkService->exportToXML($module, $this->manifestFileHandle);
	}

	/**
	 * Export cron tasks.
	 * 
	 * @param Models\Module $module
	 * @return void
	 */
	private function export_CronTasks(Models\Module $module): void
	{
		$cronService = \App\ModuleManagement\ServiceLocator::getCronService();
		$cronService->exportToXML($module, $this->manifestFileHandle);
	}

	/**
	 * Export inventory fields.
	 * 
	 * @param Models\Module $module
	 * @return void
	 */
	private function exportInventory(Models\Module $module): void
	{
		$basetable = $module->getBasetable();
		if (!$basetable) {
			return;
		}

		$invfieldTable = $basetable . '_invfield';
		$adb = \App\Database\PearDatabase::getInstance();
		
		// Check if table exists
		if (!$this->db->isTableExists($invfieldTable)) {
			return;
		}

		$result = $adb->pquery("SELECT * FROM {$invfieldTable} ORDER BY sequence", []);
		$fieldcount = $adb->num_rows($result);

		if (empty($fieldcount)) {
			return;
		}

		$this->openNode('inventoryfields');
		for ($index = 0; $index < $fieldcount; ++$index) {
			$row = $adb->fetchByAssoc($result);
			$this->openNode('inventoryfield');
			$this->outputNode($row['columnname'], 'columnname');
			$this->outputNode($row['label'], 'label');
			$this->outputNode($row['invtype'], 'invtype');
			$this->outputNode($row['presence'], 'presence');
			$this->outputNode($row['defaultvalue'] ?? '', 'defaultvalue');
			$this->outputNode($row['sequence'], 'sequence');
			$this->outputNode($row['block'], 'block');
			$this->outputNode($row['displaytype'], 'displaytype');
			$this->outputNode($row['params'] ?? '', 'params');
			$this->outputNode($row['colspan'], 'colspan');
			$this->closeNode('inventoryfield');
		}
		$this->closeNode('inventoryfields');
	}

	/**
	 * Copy directory to ZIP.
	 * 
	 * @param ZipArchive $zip
	 * @param string $sourceDir
	 * @param string $zipDir
	 * @return void
	 */
	private function copyDirectoryToZip(ZipArchive $zip, string $sourceDir, string $zipDir): void
	{
		if (!is_dir($sourceDir)) {
			return;
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item) {
			if ($item->isFile()) {
				$filePath = $item->getRealPath();
				$relativePath = str_replace('\\', '/', substr($filePath, strlen(realpath($sourceDir)) + 1));
				$zipPath = rtrim($zipDir, '/') . '/' . ltrim($relativePath, '/');
				$zip->addFile($filePath, $zipPath);
			}
		}
	}

	/**
	 * Copy language files to ZIP.
	 * Copies JSON language files (YetiForce compatible format).
	 * 
	 * @param ZipArchive $zip
	 * @param string $module
	 * @return void
	 */
	private function __copyLanguageFiles(ZipArchive $zip, string $module): void
	{
		$languageFolder = 'languages';
		if ($dir = @opendir($languageFolder)) {
			while (($langName = readdir($dir)) !== false) {
				if ($langName != '..' && $langName != '.' && is_dir($languageFolder . "/" . $langName)) {
					$langDir = @opendir($languageFolder . '/' . $langName);
					while (($moduleLangFile = readdir($langDir)) !== false) {
						$langFilePath = $languageFolder . '/' . $langName . '/' . $moduleLangFile;
						// Copy JSON language files
						if (is_file($langFilePath) && $moduleLangFile === $module . '.json') {
							$zip->addFile($langFilePath, $langFilePath);
						} elseif (is_dir($langFilePath) && $moduleLangFile == 'Settings') {
							$settingsLangDir = @opendir($langFilePath);
							while ($settingLangFileName = readdir($settingsLangDir)) {
								$settingsLangFilePath = $languageFolder . '/' . $langName . '/' . $moduleLangFile . '/' . $settingLangFileName;
								// Copy JSON language files for Settings modules
								if (is_file($settingsLangFilePath) && $settingLangFileName === $module . '.json') {
									$zip->addFile($settingsLangFilePath, $settingsLangFilePath);
								}
							}
							closedir($settingsLangDir);
						}
					}
					closedir($langDir);
				}
			}
			closedir($dir);
		}
	}

	/**
	 * Force download ZIP file.
	 * 
	 * @param string $zipfileName
	 * @return void
	 */
	private function forceDownload(string $zipfileName): void
	{
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private", false);
		header("Content-Type: application/zip");
		header("Content-Disposition: attachment; filename=\"" . basename($zipfileName) . "\"");
		$disk_file_size = filesize($zipfileName);
		$zipfilesize = $disk_file_size + ($disk_file_size % 1024);
		header("Content-Length: " . $zipfilesize);
		$fileContent = fread(fopen($zipfileName, "rb"), $zipfilesize);
		echo $fileContent;
	}

	/**
	 * Update module from ZIP file.
	 * 
	 * @param Models\Module $module
	 * @param string $zipfile
	 * @param bool $overwrite
	 * @return void
	 * @throws \Exception
	 */
	public function update(Models\Module $module, string $zipfile, bool $overwrite = true): void
	{
		$moduleName = $this->getModuleNameFromZip($zipfile);
		if ($moduleName == null) {
			throw new \Exception("Cannot get module name from ZIP file");
		}

		if ($module->getName() != $moduleName) {
			throw new \Exception("Module name mismatch!");
		}

		// Handle module bundles
		if ($this->isModuleBundle()) {
			$buildModuleArray = [];
			$installSequenceArray = [];
			$moduleList = (array) $this->modulexml->modulelist;
			foreach ($moduleList as $moduleInfos) {
				foreach ($moduleInfos as $moduleInfo) {
					$moduleInfo = (array) $moduleInfo;
					$buildModuleArray[] = $moduleInfo;
					$installSequenceArray[] = $moduleInfo['install_sequence'];
				}
			}
			sort($installSequenceArray);
			
			$zip = new ZipArchive();
			$zip->open($zipfile);
			$tempPath = $this->getTemporaryFilePath();
			$zip->extractTo($tempPath);
			$zip->close();
			
			foreach ($installSequenceArray as $sequence) {
				foreach ($buildModuleArray as $moduleInfo) {
					if ($moduleInfo['install_sequence'] == $sequence) {
						$updateModule = \App\ModuleManagement\ServiceLocator::getModuleService()->getInstance($moduleInfo['name']);
						if ($updateModule) {
							$this->update($updateModule, $this->getTemporaryFilePath($moduleInfo['filepath']), $overwrite);
						}
					}
				}
			}
			return;
		}

		// Single module update
		$moduleName = $this->initUpdate($module, $zipfile, $overwrite);
		if ($moduleName) {
			$this->update_Module($module);
		}
	}

	/**
	 * Initialize update - extract files.
	 * 
	 * @param Models\Module $module
	 * @param string $zipfile
	 * @param bool $overwrite
	 * @return string|null Module name
	 */
	private function initUpdate(Models\Module $module, string $zipfile, bool $overwrite): ?string
	{
		$moduleName = $this->getModuleNameFromZip($zipfile);
		if ($moduleName == null || $module->getName() != $moduleName) {
			return null;
		}

		$zip = new ZipArchive();
		if ($zip->open($zipfile) !== true) {
			throw new \Exception("Cannot open ZIP file: $zipfile");
		}

		$defaultLayout = \App\Runtime\CRM_Viewer::getDefaultLayoutName();
		
		// Extract files with path mapping (same as initImport)
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$filename = $zip->getNameIndex($i);
			if ($filename === false) {
				continue;
			}

			if (substr($filename, -1) === '/') {
				continue;
			}

			$targetPath = null;
			
			if (strpos($filename, 'templates/') === 0) {
				$targetPath = "layouts/$defaultLayout/modules/$moduleName/" . substr($filename, 10);
			} elseif (strpos($filename, "modules/$moduleName/") === 0) {
				$targetPath = $filename;
			} elseif (strpos($filename, 'cron/') === 0) {
				$targetPath = "cron/modules/$moduleName/" . substr($filename, 5);
			} elseif (strpos($filename, 'languages/') === 0) {
				$targetPath = $filename;
			} elseif (strpos($filename, 'settings/actions/') === 0) {
				$targetPath = "modules/Settings/$moduleName/actions/" . substr($filename, 17);
			} elseif (strpos($filename, 'settings/views/') === 0) {
				$targetPath = "modules/Settings/$moduleName/views/" . substr($filename, 15);
			} elseif (strpos($filename, 'settings/models/') === 0) {
				$targetPath = "modules/Settings/$moduleName/models/" . substr($filename, 16);
			} elseif (strpos($filename, 'settings/templates/') === 0) {
				$targetPath = "layouts/$defaultLayout/modules/Settings/$moduleName/" . substr($filename, 19);
			} elseif (strpos($filename, 'settings/connectors/') === 0) {
				$targetPath = "modules/Settings/$moduleName/connectors/" . substr($filename, 20);
			} elseif (strpos($filename, 'settings/libraries/') === 0) {
				$targetPath = "modules/Settings/$moduleName/libraries/" . substr($filename, 19);
			} elseif (strpos($filename, 'layouts/') === 0) {
				$targetPath = $filename;
			}

			if ($targetPath && ($overwrite || !file_exists($targetPath))) {
				$dir = dirname($targetPath);
				if (!is_dir($dir)) {
					mkdir($dir, 0755, true);
				}
				file_put_contents($targetPath, $zip->getFromIndex($i));
			}
		}

		$zip->close();
		return $moduleName;
	}

	/**
	 * Update module from manifest.
	 * 
	 * @param Models\Module $module
	 * @return void
	 * @throws \Exception
	 */
	private function update_Module(Models\Module $module): void
	{
		$tabname = (string) $this->modulexml->name;
		$tablabel = (string) $this->modulexml->label;
		$tabversion = (string) $this->modulexml->version;

		$this->eventDispatcher->fire($module->getName(), 'module.preupdate');

		// Update module metadata
		$moduleService = \App\ModuleManagement\ServiceLocator::getModuleService();
		$updatedModule = new Models\Module(
			$module->getId(),
			$tabname,
			$tablabel,
			$tabversion ? (int) $tabversion : $module->getVersion(),
			$module->getMinversion(),
			$module->getMaxversion(),
			$module->getPresence(),
			$module->getOwnedby(),
			$module->getTabsequence(),
			$module->getParent(),
			$module->getCustomized(),
			$module->getIsentitytype(),
			$module->getEntityidcolumn(),
			$module->getEntityidfield(),
			$module->getBasetable(),
			$module->getBasetableid(),
			$module->getCustomtable(),
			$module->getGrouptable(),
			$module->getType(),
			null
		);
		$moduleService->update($module->getId(), $updatedModule);

		// Handle migrations
		$this->handle_Migration($this->modulexml, $module);

		// Update components
		$this->update_Tables($this->modulexml);
		$this->update_Blocks($this->modulexml, $module);
		$this->update_CustomViews($this->modulexml, $module);
		$this->update_SharingAccess($this->modulexml, $module);
		$this->update_Events($this->modulexml, $module);
		$this->update_Actions($this->modulexml, $module);
		$this->update_RelatedLists($this->modulexml, $module);
		$this->update_CustomLinks($this->modulexml, $module);
		$this->update_CronTasks($this->modulexml);

		$this->eventDispatcher->fire($module->getName(), 'module.postupdate');
	}

	/**
	 * Handle migrations.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @param Models\Module $module
	 * @return void
	 */
	private function handle_Migration(\SimpleXMLElement $modulenode, Models\Module $module): void
	{
		if (empty($modulenode->migrations) || empty($modulenode->migrations->migration)) {
			return;
		}

		$cur_version = $module->getVersion();
		// Normalize version to string format for version_compare
		// version_compare() handles different formats correctly (e.g., "1" < "1.1" < "1.19")
		$cur_version_str = (string) $cur_version;
		$migrations = [];

		foreach ($modulenode->migrations->migration as $migrationnode) {
			$migrationattrs = $migrationnode->attributes();
			$migrationversion = (string) $migrationattrs['version'];
			$migrations[$migrationversion] = $migrationnode;
		}

		uksort($migrations, 'version_compare');

		foreach ($migrations as $migversion => $migrationnode) {
			if (version_compare($cur_version_str, $migversion, '<')) {
				if (!empty($migrationnode->tables) && !empty($migrationnode->tables->table)) {
					foreach ($migrationnode->tables->table as $tablenode) {
						$tablename = (string) $tablenode->name;
						$tablesql = (string) $tablenode->sql;
						if ($this->isDestructiveSql($tablesql)) {
							// Skip destructive SQL
							continue;
						}
						$this->executeQuery($tablesql, true);
					}
				}
			}
		}
	}

	/**
	 * Update tables.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @return void
	 */
	private function update_Tables(\SimpleXMLElement $modulenode): void
	{
		$this->import_Tables($modulenode);
	}

	/**
	 * Update blocks.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @param Models\Module $module
	 * @return void
	 */
	private function update_Blocks(\SimpleXMLElement $modulenode, Models\Module $module): void
	{
		if (empty($modulenode->blocks) || empty($modulenode->blocks->block)) {
			return;
		}

		$blockService = \App\ModuleManagement\ServiceLocator::getBlockService();
		$existingBlocks = $blockService->getAllForModule($module->getId());
		$existingBlockLabels = array_map(function($b) { return $b->getLabel(); }, $existingBlocks);
		$manifestBlockLabels = [];

		foreach ($modulenode->blocks->block as $blocknode) {
			$blocklabel = (string) $blocknode->label;
			$manifestBlockLabels[] = $blocklabel;

			$existingBlock = null;
			foreach ($existingBlocks as $b) {
				if ($b->getLabel() === $blocklabel) {
					$existingBlock = $b;
					break;
				}
			}

			if ($existingBlock) {
				// Update existing block
				$updatedBlock = new Models\Block(
					$existingBlock->getId(),
					$blocklabel,
					isset($blocknode->sequence) ? (int) $blocknode->sequence : $existingBlock->getSequence(),
					isset($blocknode->show_title) ? (int) $blocknode->show_title : $existingBlock->getShowtitle(),
					isset($blocknode->visible) ? (int) $blocknode->visible : $existingBlock->getVisible(),
					isset($blocknode->create_view) ? (int) $blocknode->create_view : $existingBlock->getIncreateview(),
					isset($blocknode->edit_view) ? (int) $blocknode->edit_view : $existingBlock->getIneditview(),
					isset($blocknode->detail_view) ? (int) $blocknode->detail_view : $existingBlock->getIndetailview(),
					isset($blocknode->display_status) ? (int) $blocknode->display_status : $existingBlock->getDisplay_status(),
					isset($blocknode->iscustom) ? (int) $blocknode->iscustom : $existingBlock->getIscustom(),
					null
				);
				$blockService->update($existingBlock->getId(), $updatedBlock);
				$this->update_Fields($blocknode, $updatedBlock, $module);
			} else {
				// Create new block
				$block = $this->import_Block($modulenode, $module, $blocknode);
				$this->import_Fields($blocknode, $block, $module);
			}
		}

		// Delete removed blocks
		foreach ($existingBlocks as $block) {
			if (!in_array($block->getLabel(), $manifestBlockLabels)) {
				$blockService->delete($block->getId());
			}
		}
	}

	/**
	 * Update fields.
	 * 
	 * @param \SimpleXMLElement $blocknode
	 * @param Models\Block $block
	 * @param Models\Module $module
	 * @return void
	 */
	private function update_Fields(\SimpleXMLElement $blocknode, Models\Block $block, Models\Module $module): void
	{
		if (empty($blocknode->fields) || empty($blocknode->fields->field)) {
			return;
		}

		$fieldService = \App\ModuleManagement\ServiceLocator::getFieldService();
		$existingFields = [];
		$allFields = $fieldService->getAllForModule($module->getId());
		foreach ($allFields as $f) {
			if ($f->getBlock()->getId() == $block->getId()) {
				$existingFields[$f->getName()] = $f;
			}
		}
		$manifestFieldNames = [];

		foreach ($blocknode->fields->field as $fieldnode) {
			$fieldname = (string) $fieldnode->fieldname;
			$manifestFieldNames[] = $fieldname;

			$existingField = $existingFields[$fieldname] ?? null;

			if ($existingField) {
				// Update existing field
				$updatedField = new Models\Field(
					$existingField->getId(),
					$fieldname,
					$module->getId(),
					(string) $fieldnode->fieldlabel,
					(string) $fieldnode->tablename,
					(string) $fieldnode->columnname,
					isset($fieldnode->columntype) ? (string) $fieldnode->columntype : $existingField->getColumntype(),
					isset($fieldnode->helpinfo) ? (string) $fieldnode->helpinfo : $existingField->getHelpinfo(),
					isset($fieldnode->summaryfield) ? (int) $fieldnode->summaryfield : $existingField->getSummaryfield(),
					$existingField->getHeader_field(),
					$existingField->getMaxlengthtext(),
					$existingField->getMaxwidthcolumn(),
					isset($fieldnode->masseditable) ? (int) $fieldnode->masseditable : $existingField->getMasseditable(),
					(int) $fieldnode->uitype,
					(string) $fieldnode->typeofdata,
					isset($fieldnode->displaytype) ? (int) $fieldnode->displaytype : $existingField->getDisplaytype(),
					(int) $fieldnode->generatedtype,
					(int) $fieldnode->readonly,
					(int) $fieldnode->presence,
					isset($fieldnode->defaultvalue) ? (string) $fieldnode->defaultvalue : $existingField->getDefaultvalue(),
					isset($fieldnode->maximumlength) ? (int) $fieldnode->maximumlength : $existingField->getMaximumlength(),
					isset($fieldnode->sequence) ? (int) $fieldnode->sequence : $existingField->getSequence(),
					isset($fieldnode->quickcreate) ? (int) $fieldnode->quickcreate : $existingField->getQuickcreate(),
					isset($fieldnode->quickcreatesequence) ? (int) $fieldnode->quickcreatesequence : $existingField->getQuicksequence(),
					isset($fieldnode->info_type) ? (string) $fieldnode->info_type : $existingField->getInfo_type(),
					$block,
					isset($fieldnode->fieldparams) ? (string) $fieldnode->fieldparams : $existingField->getFieldparams()
				);
				$fieldService->update($existingField->getId(), $updatedField);

				// Update picklist values
				if (!empty($fieldnode->picklistvalues) && !empty($fieldnode->picklistvalues->picklistvalue)) {
					$picklistvalues = [];
					foreach ($fieldnode->picklistvalues->picklistvalue as $picklistvaluenode) {
						$picklistvalues[] = (string) $picklistvaluenode;
					}
					$fieldService->setPicklistValues($existingField->getId(), $picklistvalues);
				}

				// Update related modules
				if (!empty($fieldnode->relatedmodules) && !empty($fieldnode->relatedmodules->relatedmodule)) {
					$relatedmodules = [];
					foreach ($fieldnode->relatedmodules->relatedmodule as $relatedmodulenode) {
						$relatedmodules[] = (string) $relatedmodulenode;
					}
					$fieldService->setRelatedModules($existingField->getId(), $relatedmodules);
				}
			} else {
				// Create new field
				$this->import_Field($blocknode, $block, $module, $fieldnode);
			}
		}

		// Delete removed fields
		foreach ($existingFields as $field) {
			if (!in_array($field->getName(), $manifestFieldNames)) {
				$fieldService->delete($field->getId());
			}
		}
	}

	/**
	 * Update custom views.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @param Models\Module $module
	 * @return void
	 */
	private function update_CustomViews(\SimpleXMLElement $modulenode, Models\Module $module): void
	{
		if (empty($modulenode->customviews) || empty($modulenode->customviews->customview)) {
			return;
		}
		$filterService = \App\ModuleManagement\ServiceLocator::getFilterService();
		foreach ($modulenode->customviews->customview as $customviewnode) {
			$filterInstance = $filterService->getInstance((string) $customviewnode->viewname, $module);
			if ($filterInstance) {
				// Delete existing filter
				$filterService->delete($filterInstance['cvid']);
			}
			$filterService->importFromXML($module, $customviewnode, $this->modulefields_cache);
		}
	}

	/**
	 * Update sharing access.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @param Models\Module $module
	 * @return void
	 */
	private function update_SharingAccess(\SimpleXMLElement $modulenode, Models\Module $module): void
	{
		if (empty($modulenode->sharingaccess)) {
			return;
		}
		// Sharing access update - handled by import_SharingAccess
	}

	/**
	 * Update events.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @param Models\Module $module
	 * @return void
	 */
	private function update_Events(\SimpleXMLElement $modulenode, Models\Module $module): void
	{
		if (empty($modulenode->eventHandlers) || empty($modulenode->eventHandlers->event)) {
			return;
		}
		$moduleId = \App\Utils\ModuleUtils::getModuleId($module->getName());
		// Delete existing handlers
		$this->db->createCommand()->delete('vtiger_eventhandlers', ['owner_id' => $moduleId])->execute();
		// Import new handlers
		$this->import_Events($modulenode, $module);
	}

	/**
	 * Update actions.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @param Models\Module $module
	 * @return void
	 */
	private function update_Actions(\SimpleXMLElement $modulenode, Models\Module $module): void
	{
		if (empty($modulenode->actions) || empty($modulenode->actions->action)) {
			return;
		}
		$this->import_Actions($modulenode, $module);
	}

	/**
	 * Update related lists.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @param Models\Module $module
	 * @return void
	 */
	private function update_RelatedLists(\SimpleXMLElement $modulenode, Models\Module $module): void
	{
		$relationService = \App\ModuleManagement\ServiceLocator::getRelationService();
		$moduleService = \App\ModuleManagement\ServiceLocator::getModuleService();

		// Get all existing relations and remove them
		$existingRelations = (new \App\Db\Query())
			->from('vtiger_relatedlists')
			->where(['tabid' => $module->getId()])
			->all();

		foreach ($existingRelations as $rel) {
			$targetModule = $moduleService->getInstance($rel['related_tabid']);
			if ($targetModule) {
				$relationService->unsetRelatedList($module->getId(), $targetModule->getId(), $rel['label'], $rel['name']);
			}
		}

		// Import new relations
		$this->import_RelatedLists($modulenode, $module);
	}

	/**
	 * Update custom links.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @param Models\Module $module
	 * @return void
	 */
	private function update_CustomLinks(\SimpleXMLElement $modulenode, Models\Module $module): void
	{
		// Delete all existing links
		\App\ModuleManagement\ServiceLocator::getLinkService()->deleteAll($module->getId());
		// Import new links
		$this->import_CustomLinks($modulenode, $module);
	}

	/**
	 * Update cron tasks.
	 * 
	 * @param \SimpleXMLElement $modulenode
	 * @return void
	 */
	private function update_CronTasks(\SimpleXMLElement $modulenode): void
	{
		if (empty($modulenode->crons) || empty($modulenode->crons->cron)) {
			return;
		}
		$cronService = \App\ModuleManagement\ServiceLocator::getCronService();
		// Delete existing cron tasks for module
		$cronTasks = $cronService->listAllInstancesByModule((string) $modulenode->name);
		foreach ($modulenode->crons->cron as $importCronTask) {
			foreach ($cronTasks as $cronTask) {
				if ($cronTask['name'] == (string) $importCronTask->name && (string) $importCronTask->handler == $cronTask['handler_file']) {
					$cronService->deregister((string) $importCronTask->name);
				}
			}
			$status = empty($importCronTask->status) ? \App\ModuleManagement\Services\CronService::STATUS_DISABLED : \App\ModuleManagement\Services\CronService::STATUS_ENABLED;
			$sequence = empty($importCronTask->sequence) ? $cronService->nextSequence() : (int) $importCronTask->sequence;
			$cronService->register(
				(string) $importCronTask->name,
				(string) $importCronTask->handler,
				(string) $importCronTask->frequency,
				(string) $modulenode->name,
				$status,
				$sequence,
				(string) $importCronTask->description
			);
		}
	}
}

