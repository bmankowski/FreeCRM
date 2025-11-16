<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */



namespace App\Runtime;

use App\AppConfig;
use App\Cache\Cache;
use App\Log;
use Exception;

class CRM_Viewer extends \Smarty
{

	const DEFAULTLAYOUT = 'basic';

	const DEFAULTTHEME = 'twilight';

	static $currentLayout;

	// Turn-it on to analyze the data pushed to templates for the request.
	protected static $debugViewer = false;

	protected static $instance = false;

	protected $source;

	/**
	 * log message into the file if in debug mode.
	 * @param mixed $message
	 * @param mixed $delimiter 
	 */
	protected function log($message, $delimiter = '\n')
	{
		static $file = null;
        if ($file === null) {
            $file = __DIR__ . '/../../cache/logs/viewer-debug.log';
        }

		if (self::$debugViewer) {
			file_put_contents($file, $message . $delimiter, FILE_APPEND);
		}
	}

	/**
	 * Constructor - Sets the templateDir and compileDir for the Smarty files
	 * @param string - $media Layout/Media name
	 */
	public function __construct($media = '')
	{
		parent::__construct();
		$this->debugging = \App\Core\AppConfig::debug('DISPLAY_DEBUG_VIEWER');

		$THISDIR = __DIR__;
		$compileDir = '';
		$templateDir = [];
        self::$currentLayout = empty($media) ? \App\Runtime\Yeti_Layout::getActiveLayout() : $media;

		if (\App\Core\AppConfig::performance('LOAD_CUSTOM_FILES')) {
			$templateDir[] = $THISDIR . '/../../custom/layouts/' . self::$currentLayout;
		}

		$templateDir[] = $THISDIR . '/../../layouts/' . self::$currentLayout;
		$compileDir = $THISDIR . '/../../cache/templates_c/' . self::$currentLayout;
		if (\App\Core\AppConfig::performance('LOAD_CUSTOM_FILES')) {
			$templateDir[] = $THISDIR . '/../../custom/layouts/' . self::getDefaultLayoutName();
		}

		$templateDir[] = $THISDIR . '/../../layouts/' . self::getDefaultLayoutName();
		if (!file_exists($compileDir)) {
			mkdir($compileDir, 0777, true);
		}

		$this->setTemplateDir(array_unique($templateDir));
		$this->setCompileDir($compileDir);

		self::$debugViewer = \App\Core\AppConfig::debug('DEBUG_VIEWER');

		// FOR SECURITY
		// Escape all {$variable} to overcome XSS
		// We need to use {$variable nofilter} to overcome double escaping
		static $debugViewerURI = false;
		if (self::$debugViewer && $debugViewerURI === false) {
			$debugViewerURI = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
			if ($_POST !== []) {
				$debugViewerURI .= '?' . http_build_query($_POST);
			} else {
				$debugViewerURI = $_SERVER['REQUEST_URI'];
			}

			$this->log(sprintf('URI: %s, TYPE: ', $debugViewerURI) . $_SERVER['REQUEST_METHOD']);
		}

		$this->registerSmartyPlugins();
		
		// Ensure YETIFORCE_VERSION is always available in templates
		$this->assign('YETIFORCE_VERSION', \App\Core\Version::get());
		
		// Assign default template variables to prevent undefined key warnings
		$this->assignDefaultTemplateVariables();
	}
	
	/**
	 * Assign default template variables to prevent undefined key warnings
	 * These defaults will be overridden by controllers when they set actual values
	 */
	protected function assignDefaultTemplateVariables()
	{
		$this->assign('PAGETITLE', '');
		$this->assign('QUALIFIED_MODULE', '');
		$this->assign('MODULE', '');
		$this->assign('MODULE_NAME', '');
		$this->assign('VIEW', '');
		$this->assign('PARENT_MODULE', '');
		$this->assign('STYLES', []);
		$this->assign('HEADER_SCRIPTS', []);
		$this->assign('FOOTER_SCRIPTS', []);
		$this->assign('SKIN_PATH', '');
		$this->assign('LAYOUT_PATH', 'layouts/' . self::getLayoutName());
		$this->assign('LANGUAGE_STRINGS', []);
		$this->assign('HTMLLANG', 'en');
		$this->assign('LANGUAGE', 'en_us');
		$this->assign('ACTIVITY_REMINDER', 0);
		$this->assign('MENUS', []);
		$this->assign('MENU_HEADER_LINKS', []);
		$this->assign('SEARCHABLE_MODULES', []);
		$this->assign('CHAT_ACTIVE', false);
		$this->assign('REMINDER_ACTIVE', false);
	}

	/**
	 * Register custom Smarty plugins and classes for template use
	 */
	private function registerSmartyPlugins()
	{
		// Register custom functions for Smarty 4.5 compatibility
		// Functions in global namespace (from TemplateHelpers.php) can be called directly in templates
		try {
			// Register plugins - these are in global namespace from TemplateHelpers.php
			$this->registerPlugin('modifier', 'vtranslate', '\App\Runtime\Vtiger_Language_Handler::translate');
			$this->registerPlugin('function', 'vimage_path', 'vimage_path');
			$this->registerPlugin('modifier', 'vimage_path', 'vimage_path'); // Also as modifier
			$this->registerPlugin('function', 'vtemplate_path', 'vtemplate_path');
			$this->registerPlugin('modifier', 'vtemplate_path', 'vtemplate_path'); // Also as modifier
			$this->registerPlugin('function', 'vresource_url', 'vresource_url');
			$this->registerPlugin('modifier', 'vresource_url', 'vresource_url'); // Also as modifier


			// Register  modifier 't'
			$this->registerPlugin('modifier', 't', '\App\Runtime\Vtiger_Language_Handler::translate');
			
		// Register static classes for template use
		$this->registerClass('AppConfig', '\App\Core\AppConfig');
		$this->registerClass('\App\Modules\Base\Models\Menu', '\App\Modules\\Base\Models\\Menu');
		$this->registerClass('\App\Runtime\Yeti_Layout', '\App\\Runtime\\Yeti_Layout');
		$this->registerClass('\App\Modules\Settings\WidgetsManagement\Models\Module', '\App\Modules\\Settings\\WidgetsManagement\Models\\Module');
		$this->registerClass('\App\Modules\Settings\Calendar\Models\Module', '\App\Modules\\Settings\\Calendar\Models\\Module');
		$this->registerClass('\App\\Utils\\Json', '\App\\Utils\\Json');
		$this->registerClass('\App\\Debug\\Debugger', '\App\\Debug\\Debugger');
		$this->registerClass('App\\Core\\Company', '\App\\Core\\Company');
		$this->registerClass('\App\\Records\\Record', '\App\\Records\\Record');
		// Register UIType and utility classes used in templates
		$this->registerClass('\App\\Fields\\Owner', '\App\\Fields\\Owner');
		$this->registerClass('\App\\Fields\\DateTimeField', '\App\\Fields\\DateTimeField');
		$this->registerClass('CurrencyField', '\App\\Fields\\CurrencyField');
		$this->registerClass('vtlib\\Functions', 'vtlib\\Functions');
		// Register additional model classes used in templates
		$this->registerClass('\App\\Modules\\Users\\Models\\Privileges', '\App\\Modules\\Users\\Models\\Privileges');
		$this->registerClass('\App\\Modules\\Users\\Models\\Record', '\App\\Modules\\Users\\Models\\Record');
		$this->registerClass('\App\\Modules\\Settings\\Roles\\Models\\Record', '\App\\Modules\\Settings\\Roles\\Models\\Record');
		$this->registerClass('\App\\Modules\\Base\\Models\\Module', '\App\\Modules\\Base\\Models\\Module');
		$this->registerClass('\App\\Modules\\Base\\Models\\Field', '\App\\Modules\\Base\\Models\\Field');
		$this->registerClass('\App\\Modules\\Base\\Models\\InventoryField', '\App\\Modules\\Base\\Models\\InventoryField');
		$this->registerClass('\App\\Modules\\Base\\Helpers\\Util', '\App\\Modules\\Base\\Helpers\\Util');
		$this->registerClass('\App\\Security\\Privilege', '\App\\Security\\Privilege');
		$this->registerClass('\App\\Modules\\Users\\Models\\Colors', '\App\\Modules\\Users\\Models\\Colors');
		$this->registerClass('\App\\Modules\\Settings\\ModuleManager\\Models\\Library', '\App\\Modules\\Settings\\ModuleManager\\Models\\Library');
		$this->registerClass('App\Modules\Settings\Mail\Models\Config', '\App\\Modules\\Settings\\Mail\\Models\\Config');
		$this->registerClass('OSSMail_Autologin_Model', '\App\\Modules\\OSSMail\\Models\\Autologin');
		$this->registerClass('OSSMail_Module_Model', '\App\\Modules\\OSSMail\\Models\\Module');

		// Register PHP functions that are used in templates
		$this->registerPlugin('modifier', 'strpos', 'strpos');
		$this->registerPlugin('modifier', 'strrpos', 'strrpos');
		$this->registerPlugin('modifier', 'stripos', 'stripos');
		$this->registerPlugin('modifier', 'strtoupper', 'strtoupper');
		$this->registerPlugin('modifier', 'lcfirst', 'lcfirst');
		$this->registerPlugin('modifier', 'array_flip', 'array_flip');
		$this->registerPlugin('modifier', 'array_diff_key', 'array_diff_key');
		$this->registerPlugin('modifier', 'explode', 'explode');
		$this->registerPlugin('modifier', 'htmlspecialchars', 'htmlspecialchars');
		$this->registerPlugin('modifier', 'file_exists', 'file_exists');
		$this->registerPlugin('modifier', 'intval', 'intval');
		$this->registerPlugin('modifier', 'decode_html', '\App\Utils\ListViewUtils::decodeHtml');
		$this->registerPlugin('modifier', 'trim', 'trim');
		$this->registerPlugin('modifier', 'html_entity_decode', 'html_entity_decode');
		$this->registerPlugin('modifier', 'array_key_exists', 'array_key_exists');
		$this->registerPlugin('modifier', 'microtime', 'microtime');
		$this->registerPlugin('modifier', 'sprintf', 'sprintf');
		$this->registerPlugin('modifier', 'array_map', 'array_map');
		$this->registerPlugin('modifier', 'method_exists', 'method_exists');
		$this->registerPlugin('modifier', 'get_class', 'get_class');
		// Register json_decode modifier - wrapper for \App\Utils\Json::decode with support for assoc parameter
		$this->registerPlugin('modifier', 'json_decode', [self::class, 'jsonDecodeModifier']);
		$this->registerPlugin('function', 'strpos', 'strpos');
		$this->registerPlugin('function', 'explode', 'explode');
		$this->registerPlugin('function', 'htmlspecialchars', 'htmlspecialchars');
		$this->registerPlugin('function', 'file_exists', 'file_exists');
		$this->registerPlugin('function', 'intval', 'intval');
		$this->registerPlugin('function', 'strtoupper', 'strtoupper');
		$this->registerPlugin('function', 'decode_html', '\App\Utils\ListViewUtils::decodeHtml');
		$this->registerPlugin('function', 'trim', 'trim');
		$this->registerPlugin('function', 'html_entity_decode', 'html_entity_decode');
		$this->registerPlugin('function', 'array_key_exists', 'array_key_exists');
		$this->registerPlugin('function', 'microtime', 'microtime');
		$this->registerPlugin('function', 'sprintf', 'sprintf');
		$this->registerPlugin('function', 'array_map', 'array_map');
		$this->registerPlugin('function', 'method_exists', 'method_exists');
		$this->registerPlugin('function', 'get_class', 'get_class');



		} catch (Exception $exception) {
			// Log error but don't break the application
			\App\Log\Log::error('Smarty plugin registration error: ' . $exception->getMessage());
			throw $exception;
		}
	}

	/**
	 * Smarty modifier wrapper for json_decode
	 * Supports the assoc parameter for returning associative arrays
	 * @param string $json JSON string to decode
	 * @param bool $assoc Whether to return associative arrays (default: true)
	 * @return mixed Decoded JSON data
	 */
	public static function jsonDecodeModifier($json, $assoc = true)
	{
		return \App\Utils\Json::decode($json, $assoc ? \App\Utils\Json::TYPE_ARRAY : \App\Utils\Json::TYPE_OBJECT);
	}

	/**
	 * Function to get the current layout name
	 * @return string - Current layout name if not empty, otherwise Default layout name
	 */
	public static function getLayoutName()
	{
		if (!empty(self::$currentLayout)) {
			return self::$currentLayout;
		}

		return self::getDefaultLayoutName();
	}

	/**
	 * Function to return for default layout name
	 * @return string - Default Layout Name
	 */
	public static function getDefaultLayoutName()
	{
		return self::DEFAULTLAYOUT;
	}

	/**
	 * Function to get the module specific template path for a given template
	 * @param string $templateName
	 * @param string $moduleName
	 * @return string - Module specific template path if exists, otherwise default template path for the given template name
	 */
	public function getTemplatePath($templateName, $moduleName = '')
	{
		// Validate template name to prevent concatenation issues
		if (empty($templateName) || !is_string($templateName)) {
			throw new \Exception('Invalid template name provided');
		}

		$moduleName = str_replace(':', '/', (string)$moduleName);
		$cacheKey = $templateName . $moduleName;
		// TODO: BMN repair Ten cache tutaj zwaraca połączone wartości, nie wiem skąd to się bierze
		// if (\App\Cache\Cache::has('ViewerTemplatePath', $cacheKey)) {
		// 	return \App\Cache\Cache::get('ViewerTemplatePath', $cacheKey);
		// }
		$possibleTemplateDirs = $this->getTemplateDir();
		foreach ($possibleTemplateDirs as $possibleTemplateDir) {
			$completeFilePath = $possibleTemplateDir . sprintf('modules/%s/%s', $moduleName, $templateName);
			if (!empty($moduleName) && file_exists($completeFilePath)) {
				$filePath = sprintf('modules/%s/%s', $moduleName, $templateName);
			} else {
				// Fall back lookup on actual module, in case where parent module doesn't contain actual module within in (directory structure)
				if (strpos($moduleName, '/') > 0) {
					$moduleHierarchyParts = explode('/', $moduleName);
					$actualModuleName = $moduleHierarchyParts[count($moduleHierarchyParts) - 1];
					$baseModuleName = $moduleHierarchyParts[0];
					$fallBackOrder = [
						$actualModuleName,
						$baseModuleName . '/Base'
					];
					foreach ($fallBackOrder as $fallBackModuleName) {
						$intermediateFallBackFileName = 'modules/' . $fallBackModuleName . '/' . $templateName;
						$intermediateFallBackFilePath = $possibleTemplateDir . DIRECTORY_SEPARATOR . $intermediateFallBackFileName;
						if (file_exists($intermediateFallBackFilePath)) {
							Cache::save('ViewerTemplatePath', $cacheKey, $intermediateFallBackFileName, Cache::LONG);
							return $intermediateFallBackFileName;
						}
					}
				}

				$filePath = 'modules/Base/' . $templateName;
			}
		}

		Cache::save('ViewerTemplatePath', $cacheKey, $filePath, Cache::LONG);
		return $filePath;
	}

	/**
	 * Function to display/fetch the smarty file contents
	 * @param string $templateName
	 * @param string $moduleName
	 * @param boolean $fetch
	 * @return string|bool HTML data
	 */
	public function view($templateName, $moduleName = '', $fetch = false)
	{
		$templatePath = $this->getTemplatePath($templateName, $moduleName);
		if (Cache::has('ViewerTemplateExists', $templatePath)) {
			$templateFound = Cache::get('ViewerTemplateExists', $templatePath);
		} else {
			$templateFound = $this->templateExists($templatePath);
			Cache::save('ViewerTemplateExists', $templatePath, $templateFound, Cache::LONG);
		}

		// Logging
		if (self::$debugViewer) {
			$templatePathToLog = $templatePath;
			$qualifiedModuleName = str_replace(':', '/', $moduleName);
			// In case we found a fallback template, log both lookup and target template resolved to.
			if (!empty($moduleName) && strpos($templatePath, sprintf('modules/%s/', $qualifiedModuleName)) !== 0) {
				$templatePathToLog = sprintf('modules/%s/%s > %s', $qualifiedModuleName, $templateName, $templatePath);
			}

			$this->log(sprintf('VIEW: %s, FOUND: ', $templatePathToLog) . ($templateFound ? "1" : "0"));
			foreach ($this->tpl_vars as $key => $smarty_variable) {
				// Determine type of value being pased.
				$valueType = 'literal';
                if (is_object($smarty_variable->value)) {
                    $valueType = get_class($smarty_variable->value);
                } elseif (is_array($smarty_variable->value)) {
                    $valueType = 'array';
                }

				$this->log(sprintf("DATA: %s, TYPE: %s", $key, $valueType));
			}
		}

		// END
		if ($templateFound) {
			if (!empty(\App\Core\AppConfig::debug('SMARTY_ERROR_REPORTING'))) {
				$this->error_reporting = \App\Core\AppConfig::debug('SMARTY_ERROR_REPORTING');
			}

			if ($fetch) {
				return $this->fetch($templatePath);
			}

            $this->display($templatePath);

			return true;
		}

		return false;
	}

	/**
	 * Static function to get the Instance of the Class Object
	 * @param string $media Layout/Media
	 * @return CRM_Viewer instance
	 */
	public static function getInstance($media = '')
	{
		if (self::$instance instanceof self) {
			return self::$instance;
		}

		$instance = new self($media);
		self::$instance = $instance;
		return $instance;
	}
}

