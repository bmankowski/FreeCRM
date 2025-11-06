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

/**
 * Abstract Base View Controller Class
 */

namespace App\Base\Controllers;


abstract class BaseViewController extends \App\Base\Controllers\BaseActionController
{

	public $pageTitle;
	protected $viewer;

	public function __construct()
	{
		parent::__construct();
	}
	public function preProcess(\App\Http\Vtiger_Request $vtigerRequest, $display = true)
	{
		$moduleName = $vtigerRequest->getModule();
		$viewer = $this->getViewer($vtigerRequest);
		$viewer->assign('PAGETITLE', $this->getPageTitle($vtigerRequest));
		$viewer->assign('BREADCRUMB_TITLE', $this->getBreadcrumbTitle($vtigerRequest));
		$viewer->assign('BREADCRUMBS', $this->buildBreadcrumbs($vtigerRequest));
		$viewer->assign('BREADCRUMBS_SEPARATOR', $this->getBreadcrumbsSeparator());
		$viewer->assign('HEADER_SCRIPTS', $this->getHeaderScripts($vtigerRequest));
		$viewer->assign('FOOTER_SCRIPTS', $this->getFooterScripts($vtigerRequest));
		$viewer->assign('STYLES', $this->getHeaderCss($vtigerRequest));
		$viewer->assign('SKIN_PATH', \App\Runtime\Vtiger_Theme::getCurrentUserThemePath());
		$viewer->assign('LAYOUT_PATH', 'layouts/' . \App\Runtime\Yeti_Layout::getActiveLayout());
		$viewer->assign('LANGUAGE_STRINGS', $this->getJSLanguageStrings($vtigerRequest));
		$viewer->assign('HTMLLANG', \App\Runtime\Vtiger_Language_Handler::getShortLanguageName());
		$viewer->assign('LANGUAGE', \App\Runtime\Vtiger_Language_Handler::getLanguage());
		$viewer->assign('SHOW_BODY_HEADER', $this->showBodyHeader());
		$viewer->assign('USER_MODEL', $vtigerRequest->getUser());
		$viewer->assign('APPTITLE', \App\Runtime\Vtiger_Language_Handler::translate('APPTITLE'));
		$viewer->assign('YETIFORCE_VERSION', \App\Version::get());
		$viewer->assign('MODULE_NAME', $vtigerRequest->getModule());

		//    $viewer->assign('MODULE', $moduleName);
		$viewer->assign('VIEW', $vtigerRequest->get('view'));
		$userModel = $vtigerRequest->getUser();
		$viewer->assign('ACTIVITY_REMINDER', $userModel ? $userModel->getCurrentUserActivityReminderInSeconds() : 0);
		// Calculate script execution time
		$viewer->assign('SCRIPT_TIME', isset($GLOBALS['startTime']) ? round(microtime(true) - $GLOBALS['startTime'], 3) : 0);
		$viewer->assign('PARENT_MODULE', $vtigerRequest->get('parent'));

		// Build array of all module active statuses for templates
		$activeModules = [];
		$allModules = \vtlib\Functions::getAllModules(false, true);  // Get ALL modules, not just entity types
		foreach ($allModules as $module) {
			$activeModules[$module['name']] = \App\Module::isModuleActive($module['name']);
		}
		$viewer->assign('ACTIVE_MODULES', $activeModules);
	}

	public function getViewer(\App\Http\Vtiger_Request $vtigerRequest)
	{
		if ($this->viewer === null) {
			$viewer = \App\Runtime\CRM_Viewer::getInstance();
			if ($vtigerRequest->isAjax()) {
				$viewer->assign('USER_MODEL', $vtigerRequest->getUser());
				if ($vtigerRequest->get('parent') === 'Settings') {
					$viewer->assign('QUALIFIED_MODULE', $vtigerRequest->getModule(false));
				}
			}
			$this->viewer = $viewer;
		}
		return $this->viewer;
	}

	public function getPageTitle(\App\Http\Vtiger_Request $vtigerRequest)
	{
		$moduleName = $vtigerRequest->getModule(false);
		$moduleNameArray = explode(':', $moduleName);
		$moduleLabel = end($moduleNameArray) === 'Vtiger' ? 'YetiForce' : end($moduleNameArray);
		$title = \App\Runtime\Vtiger_Language_Handler::translate($moduleLabel, $moduleName);
		$pageTitle = $this->getBreadcrumbTitle($vtigerRequest);
		if ($pageTitle) {
			$title .= ' - ' . $pageTitle;
		}

		return $title;
	}

	public function getBreadcrumbTitle(\App\Http\Vtiger_Request $vtigerRequest)
	{
		if (!empty($this->pageTitle)) {
			return $this->pageTitle;
		}
		return 0;
	}
	protected function getBreadcrumbsSeparator()
	{
		$sep = \App\AppConfig::main('breadcrumbs_separator');
		if (empty($sep)) {
			$sep = '>';
		}
		return $sep;
	}
	/**
	 * Build breadcrumbs array for the current page
	 * @param \App\Http\Vtiger_Request $request
	 * @return array
	 */
	protected function buildBreadcrumbs(\App\Http\Vtiger_Request $request)
	{
		$breadcrumbs = [];
		$pageTitle = $this->getBreadcrumbTitle($request);

		// Load menu structure
		$userPrivModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();

		// If no user is logged in (e.g., on Login page), return empty breadcrumbs
		if (!$userPrivModel) {
			return $breadcrumbs;
		}

		$roleMenu = 'user_privileges/menu_' . filter_var($userPrivModel->get('roleid'), FILTER_SANITIZE_NUMBER_INT) . '.php';
		if (file_exists($roleMenu)) {
			require($roleMenu);
		} else {
			require('user_privileges/menu_0.php');
		}
		if (count($menus) == 0) {
			require('user_privileges/menu_0.php');
		}

		$moduleName = $request->getModule();
		$view = $request->get('view');
		$parent = $request->get('parent');

		// Regular module breadcrumbs (not Settings)
		if ($parent !== 'Settings') {
			if (empty($parent)) {
				foreach ($parentList as &$parentItem) {
					if ($moduleName == $parentItem['mod']) {
						$parent = $parentItem['parent'];
						break;
					}
				}
			}
			$parentMenu = \App\Modules\Base\Models\Menu::getParentMenu($parentList, $parent, $moduleName);
			if (count($parentMenu) > 0) {
				$breadcrumbs = array_reverse($parentMenu);
			}
			$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
			if ($moduleModel && $moduleModel->getDefaultUrl()) {
				$breadcrumbs[] = [
					'name' => \App\Runtime\Vtiger_Language_Handler::translate($moduleName, $moduleName),
					'url' => $moduleModel->getDefaultUrl()
				];
			} else {
				$breadcrumbs[] = [
					'name' => \App\Runtime\Vtiger_Language_Handler::translate($moduleName, $moduleName)
				];
			}

			if ($pageTitle) {
				$breadcrumbs[] = ['name' => \App\Runtime\Vtiger_Language_Handler::translate($pageTitle, $moduleName)];
			} elseif ($view == 'Edit' && $request->get('record') == '') {
				$breadcrumbs[] = ['name' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_CREATE', $moduleName)];
			} elseif ($view != '' && $view != 'index' && $view != 'Index') {
				$breadcrumbs[] = ['name' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_' . strtoupper($view), $moduleName)];
			} elseif ($view == '') {
				$breadcrumbs[] = ['name' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_HOME', $moduleName)];
			}
			if ($request->get('record') != '') {
				$recordLabel = \vtlib\Functions::getCRMRecordLabel($request->get('record'));
				if ($recordLabel != '') {
					$breadcrumbs[] = ['name' => $recordLabel];
				}
			}
		} else {
			// Settings module breadcrumbs
			$qualifiedModuleName = $request->getModule(false);
			
			// Settings home breadcrumb
			$breadcrumbs[] = [
				'name' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_SETTINGS', $qualifiedModuleName),
				'url' => 'index.php?module=Dashboard&parent=Settings&view=Index',
			];
			
			// Add specific settings module breadcrumb if not on Settings home
			if ($moduleName !== 'Vtiger' || $view !== 'Index') {
				$fieldId = $request->get('fieldid');
				$menu = \App\Modules\Settings\Base\Models\MenuItem::getAll();
				foreach ($menu as &$menuModel) {
					if (empty($fieldId)) {
						if ($menuModel->getModule() == $moduleName) {
							$parent = $menuModel->getMenu();
							$breadcrumbs[] = ['name' => \App\Runtime\Vtiger_Language_Handler::translate($parent->get('label'), $qualifiedModuleName)];
							$breadcrumbs[] = ['name' => \App\Runtime\Vtiger_Language_Handler::translate($menuModel->get('name'), $qualifiedModuleName),
								'url' => $menuModel->getUrl()
							];
							break;
						}
					} else {
						if ($fieldId == $menuModel->getId()) {
							$parent = $menuModel->getMenu();
							$breadcrumbs[] = ['name' => \App\Runtime\Vtiger_Language_Handler::translate($parent->get('label'), $qualifiedModuleName)];
							$breadcrumbs[] = ['name' => \App\Runtime\Vtiger_Language_Handler::translate($menuModel->get('name'), $qualifiedModuleName),
								'url' => $menuModel->getUrl()
							];
							break;
						}
					}
				}
				
				// Add page-specific breadcrumb
				if (is_array($pageTitle)) {
					foreach ($pageTitle as $title) {
						$breadcrumbs[] = $title;
					}
				} else {
					if ($pageTitle) {
						$breadcrumbs[] = ['name' => \App\Runtime\Vtiger_Language_Handler::translate($pageTitle, $moduleName)];
					} elseif ($view == 'Edit' && $request->get('record') == '' && $request->get('parent_roleid') == '') {
						$breadcrumbs[] = ['name' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_CREATE', $qualifiedModuleName)];
					} elseif ($view != '' && $view != 'List') {
						$breadcrumbs[] = ['name' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_' . strtoupper($view), $qualifiedModuleName)];
					}
					if ($request->get('record') != '' && $moduleName == 'Users') {
						$recordLabel = \App\Fields\Owner::getUserLabel($request->get('record'));
						if ($recordLabel != '') {
							$breadcrumbs[] = ['name' => $recordLabel];
						}
					}
				}
			}
		}

		return $breadcrumbs;
	}

	/**
	 * Process sidebar links to determine which one is active
	 * @param array $linkModels - Array of link models (usually from getSideBarLinks)
	 * @param \App\Http\Vtiger_Request $request - Current request
	 * @return string - Label of the active link
	 */
	protected function processSidebarLinks($linkModels, \App\Http\Vtiger_Request $request)
	{
		$currentModule = $request->get('module');
		$currentView = $request->get('view');
		$activeLinkLabel = '';

		if (isset($linkModels['SIDEBARLINK']) && is_array($linkModels['SIDEBARLINK'])) {
			foreach ($linkModels['SIDEBARLINK'] as $link) {
				$linkParams = \vtlib\Functions::getQueryParams($link->getUrl());
				if (
					isset($linkParams['module']) && isset($linkParams['view'])
					&& $currentModule == $linkParams['module']
					&& $currentView == $linkParams['view']
				) {
					$activeLinkLabel = $link->getLabel();
					$link->set('active', true);
					break;
				}
			}
		}

		return $activeLinkLabel;
	}

	protected function showBodyHeader()
	{
		return true;
	}



	/**
	 * Post process
	 * Note: Footer rendering moved to \App\Modules\Base\Views\Footer class
	 * This remains as a placeholder for views that extend BaseViewController directly
	 */
	public function postProcess(\App\Http\Vtiger_Request $vtigerRequest)
	{
		// Base implementation - Footer class overrides this with actual footer rendering
		// Views that don't extend Footer hierarchy should implement their own postProcess
	}

	/**
	 * Retrieves css styles that need to loaded in the page
	 * @param, \App\Http\Vtiger_Request $vtigerRequest - request model
	 * @return <array> - array of StyleAsset
	 */
	public function getHeaderCss(\App\Http\Vtiger_Request $vtigerRequest)
	{
		$cssFileNames = [
			'~libraries/bootstrap3/css/bootstrap.css',
			'~libraries/font-awesome/css/font-awesome.css',
			'skins.icons.userIcons',
			'skins.icons.adminIcons',
			'skins.icons.additionalIcons',
			'~libraries/jquery/chosen/chosen.css',
			'~libraries/jquery/chosen/chosen.bootstrap.css',
			'~libraries/jquery/jquery-ui/jquery-ui.css',
			'~libraries/jquery/selectize/css/selectize.bootstrap3.css',
			'~libraries/jquery/select2/select2.css',
			'~libraries/jquery/perfect-scrollbar/css/perfect-scrollbar.css',
			'~libraries/jquery/select2/select2-bootstrap.css',
			'~libraries/jquery/posabsolute-jQuery-Validation-Engine/css/validationEngine.jquery.css',
			'~libraries/jquery/pnotify/pnotify.custom.css',
			'~libraries/jquery/datepicker/css/datepicker.css',
			'~libraries/footable/css/footable.core.css',
			'~libraries/jquery/timepicker/jquery.timepicker.css',
			'~libraries/jquery/clockpicker/bootstrap-clockpicker.css',
			'libraries.resources.styles',
		];
		return $this->checkAndConvertCssStyles($cssFileNames);
	}

	/**
	 * Retrieves headers scripts that need to loaded in the page
	 * @param, \App\Http\Vtiger_Request $vtigerRequest - request model
	 * @return <array> - array of ScriptAsset
	 */
	public function getHeaderScripts(\App\Http\Vtiger_Request $vtigerRequest)
	{
		$headerScriptInstances = [
			'libraries.jquery.jquery',
			'libraries.jquery.jquery-migrate'
		];
		return $this->checkAndConvertJsScripts($headerScriptInstances);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $vtigerRequest)
	{
		$jsFileNames = [
			'~libraries/jquery/jquery.blockUI.js',
			'~libraries/jquery/chosen/chosen.jquery.js',
			'~libraries/jquery/select2/select2.full.js',
			'~libraries/jquery/jquery-ui/jquery-ui.js',
			'~libraries/jquery/jquery.class.js',
			'~libraries/jquery/defunkt-jquery-pjax/jquery.pjax.js',
			'~libraries/jquery/jstorage.js',
			'~libraries/jquery/autosize/jquery.autosize-min.js',
			'~libraries/jquery/perfect-scrollbar/js/perfect-scrollbar.jquery.js',
			'~libraries/jquery/rochal-jQuery-slimScroll/jquery.slimscroll.js',
			'~libraries/jquery/pnotify/pnotify.custom.js',
			'~libraries/jquery/jquery.hoverIntent.minified.js',
			'~libraries/bootstrap3/js/bootstrap.js',
			'~libraries/bootstrap3/js/bootstrap-switch.js',
			'~libraries/bootstrap3/js/bootbox.js',
			'~libraries/jquery/selectize/js/selectize.js',
			'~libraries/jquery/posabsolute-jQuery-Validation-Engine/js/jquery.validationEngine.js',
			'~libraries/jquery/datepicker/js/datepicker.js',
			'~libraries/jquery/dangrossman-bootstrap-daterangepicker/date.js',
			'~libraries/jquery/jquery.ba-outside-events.js',
			'~libraries/jquery/jquery.placeholder.js',
			'~libraries/jquery/dompurify/purify.js',
			'~libraries/footable/js/footable.js',
			'~libraries/resources/jquery.additions.js',
			'libraries.resources.app',
			'libraries.resources.helper',
			'libraries.resources.Connector',
			'libraries.resources.ProgressIndicator',
		];

		$languageHandlerShortName = \App\Runtime\Vtiger_Language_Handler::getShortLanguageName();
		$fileName = sprintf('libraries/jquery/posabsolute-jQuery-Validation-Engine/js/languages/jquery.validationEngine-%s.js', $languageHandlerShortName);
		if (!file_exists($fileName)) {
			$fileName = "~libraries/jquery/posabsolute-jQuery-Validation-Engine/js/languages/jquery.validationEngine-en.js";
		} else {
			$fileName = sprintf('~libraries/jquery/posabsolute-jQuery-Validation-Engine/js/languages/jquery.validationEngine-%s.js', $languageHandlerShortName);
		}

		$jsFileNames[] = $fileName;
		return $this->checkAndConvertJsScripts($jsFileNames);
	}

	public function checkAndConvertJsScripts($jsFileNames)
	{
		$fileExtension = 'js';
		$jsScriptInstances = [];

		foreach ($jsFileNames as $jFileName) {
			$jsScript = new \App\View\Assets\ScriptAsset();

			// external javascript source file handling
			if (strpos($jFileName, 'http://') === 0 || strpos($jFileName, 'https://') === 0) {
				$jsScriptInstances[$jFileName] = $jsScript->set('src', $jFileName);
				continue;
			}

			$completeFilePath = \App\Loader::resolveNameToPath($jFileName, $fileExtension);
			if (is_file($completeFilePath)) {
				if (strpos($jFileName, '~') === 0) {
					$filePath = ltrim(ltrim($jFileName, '~'), '/');
				} else {
					$filePath = str_replace('.', '/', $jFileName) . '.' . $fileExtension;
				}

				$minFilePath = str_replace('.js', '.min.js', $filePath);
				if (\vtlib\Functions::getMinimizationOptions($fileExtension) && is_file(\App\Loader::resolveNameToPath('~' . $minFilePath, $fileExtension))) {
					$filePath = $minFilePath;
				}

				$jsScriptInstances[$jFileName] = $jsScript->set('src', $filePath);
				continue;
			}
			$preLayoutPath = '';
			if (strpos($jFileName, '~') === 0) {
				$jsFile = ltrim(ltrim($jFileName, '~'), '/');
				$preLayoutPath = '~';
			} else {
				$jsFile = $jFileName;
			}
			// Checking if file exists in selected layout
			$layoutPath = 'layouts/' . \App\Runtime\Yeti_Layout::getActiveLayout();
			$fallBackFilePath = \App\Loader::resolveNameToPath($preLayoutPath . $layoutPath . '/' . $jsFile, $fileExtension);
			if (is_file($fallBackFilePath)) {
				$filePath = $jsFile;
				if ($preLayoutPath === '' || $preLayoutPath === '0') {
					$filePath = str_replace('.', '/', $filePath) . '.js';
				}

				$minFilePath = str_replace('.js', '.min.js', $filePath);
				if (\vtlib\Functions::getMinimizationOptions($fileExtension) && is_file(\App\Loader::resolveNameToPath('~' . $layoutPath . '/' . $minFilePath, $fileExtension))) {
					$filePath = $minFilePath;
				}

				$jsScriptInstances[$jFileName] = $jsScript->set('src', $layoutPath . '/' . $filePath);
				continue;
			}
			// Checking if file exists in default layout
			$layoutPath = 'layouts/' . \App\Runtime\CRM_Viewer::getDefaultLayoutName();
			$fallBackFilePath = \App\Loader::resolveNameToPath($preLayoutPath . $layoutPath . '/' . $jsFile, $fileExtension);
			if (is_file($fallBackFilePath)) {
				$filePath = $jsFile;
				if ($preLayoutPath === '' || $preLayoutPath === '0') {
					$filePath = str_replace('.', '/', $jsFile) . '.js';
				}

				$minFilePath = str_replace('.js', '.min.js', $filePath);
				if (\vtlib\Functions::getMinimizationOptions($fileExtension) && is_file(\App\Loader::resolveNameToPath('~' . $layoutPath . '/' . $minFilePath, $fileExtension))) {
					$filePath = $minFilePath;
				}

				$jsScriptInstances[$jFileName] = $jsScript->set('src', $layoutPath . '/' . $filePath);
				continue;
			}
		}

		return $jsScriptInstances;
	}

	/**
	 * Function returns the css files
	 * @param <Array> $cssFileNames
	 * @return <Array of StyleAsset>
	 *
	 * First check if $cssFileName exists
	 * if not, check under layout folder $cssFileName eg:layouts/basic/$cssFileName
	 */
	public function checkAndConvertCssStyles($cssFileNames, string $fileExtension = 'css')
	{
		$cssStyleInstances = [];
		foreach ($cssFileNames as $cssFileName) {
			$cssScriptModel = new \App\View\Assets\StyleAsset();
			if (strpos($cssFileName, 'http://') === 0 || strpos($cssFileName, 'https://') === 0) {
				$cssStyleInstances[] = $cssScriptModel->set('href', $cssFileName);
				continue;
			}

			$completeFilePath = \App\Loader::resolveNameToPath($cssFileName, $fileExtension);
			if (file_exists($completeFilePath)) {
				if (strpos($cssFileName, '~') === 0) {
					$filePath = ltrim(ltrim($cssFileName, '~'), '/');
				} else {
					$filePath = str_replace('.', '/', $cssFileName) . '.' . $fileExtension;
				}

				$minFilePath = str_replace('.css', '.min.css', $filePath);
				if (\vtlib\Functions::getMinimizationOptions($fileExtension) && is_file(\App\Loader::resolveNameToPath('~' . $minFilePath, $fileExtension))) {
					$filePath = $minFilePath;
				}

				$cssStyleInstances[$cssFileName] = $cssScriptModel->set('href', $filePath);
				continue;
			}
			$preLayoutPath = '';
			if (strpos($cssFileName, '~') === 0) {
				$cssFile = ltrim(ltrim($cssFileName, '~'), '/');
				$preLayoutPath = '~';
			} else {
				$cssFile = $cssFileName;
			}
			// Checking if file exists in selected layout
			$layoutPath = 'layouts/' . \App\Runtime\Yeti_Layout::getActiveLayout();
			$fallBackFilePath = \App\Loader::resolveNameToPath($preLayoutPath . $layoutPath . '/' . $cssFile, $fileExtension);
			if (is_file($fallBackFilePath)) {
				$filePath = $cssFile;
				if ($preLayoutPath === '' || $preLayoutPath === '0') {
					$filePath = str_replace('.', '/', $cssFile) . '.css';
				}

				$minFilePath = str_replace('.css', '.min.css', $filePath);
				if (\vtlib\Functions::getMinimizationOptions($fileExtension) && is_file(\App\Loader::resolveNameToPath('~' . $layoutPath . '/' . $minFilePath, $fileExtension))) {
					$filePath = $minFilePath;
				}

				$cssStyleInstances[$cssFileName] = $cssScriptModel->set('href', $layoutPath . '/' . $filePath);
				continue;
			}
			// Checking if file exists in default layout
			$layoutPath = 'layouts/' . \App\Runtime\CRM_Viewer::getDefaultLayoutName();
			$fallBackFilePath = \App\Loader::resolveNameToPath($preLayoutPath . $layoutPath . '/' . $cssFile, $fileExtension);
			if (is_file($fallBackFilePath)) {
				$filePath = $cssFile;
				if ($preLayoutPath === '' || $preLayoutPath === '0') {
					$filePath = str_replace('.', '/', $cssFile) . '.css';
				}

				$minFilePath = str_replace('.css', '.min.css', $filePath);
				if (\vtlib\Functions::getMinimizationOptions($fileExtension) && is_file(\App\Loader::resolveNameToPath('~' . $layoutPath . '/' . $minFilePath, $fileExtension))) {
					$filePath = $minFilePath;
				}

				$cssStyleInstances[$cssFileName] = $cssScriptModel->set('href', $layoutPath . '/' . $filePath);
				continue;
			}
		}

		return $cssStyleInstances;
	}

	/**
	 * Function returns the Client side language string
	 */
	public function getJSLanguageStrings(\App\Http\Vtiger_Request $vtigerRequest)
	{
		$moduleName = $vtigerRequest->getModule(false);
		if ($moduleName === 'Settings:Users') {
			$moduleName = 'Users';
		}

		return \App\Runtime\Vtiger_Language_Handler::export($moduleName, 'jsLanguageStrings');
	}
}

