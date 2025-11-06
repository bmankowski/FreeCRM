<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */
/* * *******************************************************************************
 * $Header$
 * Description:  Contains a variety of utility functions used to display UI
 * components such as top level menus,more menus,header links,crm logo,global search
 * and quick links of header part
 * footer is also loaded
 * function that connect to db connector to get data
 * ******************************************************************************
 * Contributor(s): YetiForce.com */


namespace App\Modules\Base\Views;

abstract class Basic extends \App\Base\Controllers\BaseViewController
{

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Function to determine file existence in relocated module folder (under vtiger6)
	 * @param string $fileuri
	 * @return bool
	 *
	 * Utility function to manage the backward compatible file load
	 * which are registered for 5.x modules (and now provided for 6.x as well).
	 */
	protected function checkFileUriInRelocatedMouldesFolder($fileuri)
	{
		if (strpos($fileuri, '?') !== false) {
			list($filename, $query) = explode('?', $fileuri);
		} else {
			$filename = $fileuri;
		}

		return file_exists($filename);
	}

	/**
	 * Function to get the list of Header Links
	 * @return <Array> - List of \App\Modules\Base\Models\Link instances
	 */
	public function getMenuHeaderLinks(\App\Http\Vtiger_Request $request)
	{
		$userModel = $request->getUser();
		$headerLinks = [];
		if (\App\Modules\Users\Models\Module::getSwitchUsers()) {
			$headerLinks[] = [
				'linktype' => 'HEADERLINK',
				'linklabel' => 'SwitchUsers',
				'linkurl' => '',
				'glyphicon' => 'glyphicon glyphicon-transfer',
				'nocaret' => true,
				'linkdata' => ['url' => $userModel->getSwitchUsersUrl()],
				'linkclass' => 'showModal',
			];
		}
		if (\App\AppConfig::security('SHOW_MY_PREFERENCES')) {
			$headerLinks[] = [
				'linktype' => 'HEADERLINK',
				'linklabel' => 'LBL_MY_PREFERENCES',
				'linkurl' => $userModel->getPreferenceDetailViewUrl(),
				'glyphicon' => 'glyphicon glyphicon-tasks',
			];
		}
		if ($userModel->isAdminUser()) {
			if ($request->get('parent') != 'Settings') {
				$headerLinks[] = [
					'linktype' => 'HEADERLINK',
					'linklabel' => 'LBL_SYSTEM_SETTINGS',
					'linkurl' => 'index.php?module=Dashboard&parent=Settings&view=Index',
					'glyphicon' => 'glyphicon glyphicon-cog',
				];
			} else {
				$headerLinks[] = [
					'linktype' => 'HEADERLINK',
					'linklabel' => 'LBL_USER_PANEL',
					'linkurl' => 'index.php',
					'glyphicon' => 'glyphicon glyphicon-user',
				];
			}
		}
		$headerLinks[] = [
			'linktype' => 'HEADERLINK',
			'linklabel' => 'LBL_SIGN_OUT',
			'linkurl' => 'index.php?module=Users&parent=Settings&action=Logout',
			'glyphicon' => 'glyphicon glyphicon-off',
			'linkclass' => 'btn-danger'
		];
		$headerLinkInstances = [];
		foreach ($headerLinks as $headerLink) {
			$headerLinkInstance = \App\Modules\Base\Models\Link::getInstanceFromValues($headerLink);
			if (isset($headerLink['childlinks'])) {
				foreach ($headerLink['childlinks'] as $childLink) {
					$headerLinkInstance->addChildLink(\App\Modules\Base\Models\Link::getInstanceFromValues($childLink));
				}
			}
			$headerLinkInstances[] = $headerLinkInstance;
		}
		$headerLinks = \App\Modules\Base\Models\Link::getAllByType(\vtlib\Link::IGNORE_MODULE, ['HEADERLINK']);
		foreach ($headerLinks as $headerType => $headerLinks) {
			foreach ($headerLinks as $headerLink) {
				$headerLinkInstances[] = \App\Modules\Base\Models\Link::getInstanceFromLinkObject($headerLink);
			}
		}
		return $headerLinkInstances;
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);

		if ($activeReminder = \App\Module::isModuleActive('Calendar')) {
			$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
			$activeReminder = $userPrivilegesModel->hasModulePermission('Calendar');
		}
		$selectedModule = $request->getModule();
		$companyDetails = \App\Company::getInstanceById();
		$companyLogo = $companyDetails->getLogo();
		$currentDate = \App\Modules\Base\UiTypes\Date::getDisplayDateValue(date('Y-n-j'));
		$viewer->assign('CURRENTDATE', $currentDate);
		$viewer->assign('MODULE', $selectedModule);
		$viewer->assign('MODULE_NAME', $selectedModule);
		$viewer->assign('QUALIFIED_MODULE', $request->getModule(false));
		$viewer->assign('PARENT_MODULE', $request->get('parent'));
		$viewer->assign('MENUS', $this->getMenu());
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('COMPANY_LOGO', $companyLogo);

		$homeModuleModel = \App\Modules\Base\Models\Module::getInstance('Home');
		$viewer->assign('HOME_MODULE_MODEL', $homeModuleModel);
		$viewer->assign('MENU_HEADER_LINKS', $this->getMenuHeaderLinks($request));
		if (\App\AppConfig::performance('GLOBAL_SEARCH')) {
			$viewer->assign('SEARCHABLE_MODULES', \App\Modules\Base\Models\Module::getSearchableModules());
		}
		if (\App\AppConfig::search('GLOBAL_SEARCH_SELECT_MODULE')) {
			$viewer->assign('SEARCHED_MODULE', $selectedModule);
		}
		$viewer->assign('CHAT_ACTIVE', \App\Module::isModuleActive('AJAXChat'));
		$viewer->assign('REMINDER_ACTIVE', $activeReminder);
		// MainLayout handles rendering, no separate preProcess template needed
	}

	protected function getMenu()
	{
		return \App\Modules\Base\Models\Menu::getAll(true);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of \App\Modules\Base\Models\JsScript instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);

		// Add header scripts from links
		$headerScripts = \App\Modules\Base\Models\Link::getAllByType(\vtlib\Link::IGNORE_MODULE, array('HEADERSCRIPT'));
		foreach ($headerScripts as $headerType => $headerScriptsValue) {
			foreach ($headerScriptsValue as $headerScript) {
				if ($this->checkFileUriInRelocatedMouldesFolder($headerScript->linkurl)) {
					$headerScriptInstances[] = \App\View\Assets\ScriptAsset::getInstanceFromLinkObject($headerScript);
				}
			}
		}

		$moduleName = $request->getModule();

		$jsFileNames = array(
			'libraries.bootstrap.js.eternicode-bootstrap-datepicker.js.bootstrap-datepicker',
			'~libraries/bootstrap/js/eternicode-bootstrap-datepicker/js/locales/bootstrap-datepicker.' . \App\Runtime\Vtiger_Language_Handler::getShortLanguageName() . '.js',
			'~libraries/jquery/timepicker/jquery.timepicker.min.js',
			'~libraries/jquery/clockpicker/jquery-clockpicker.js',
			'~libraries/jquery/inputmask/jquery.inputmask.js',
			'~libraries/jquery/mousetrap/mousetrap.min.js',
			'modules.Base.resources.Menu',
			'modules.Base.resources.Header',
			'modules.Base.resources.Edit',
			"modules.$moduleName.resources.Edit",
			'modules.Base.resources.Popup',
			"modules.$moduleName.resources.Popup",
			'modules.Base.resources.Field',
			"modules.$moduleName.resources.Field",
			'modules.Base.resources.validator.BaseValidator',
			'modules.Base.resources.validator.FieldValidator',
			"modules.$moduleName.resources.validator.FieldValidator",
			'libraries.jquery.jquery_windowmsg',
			'modules.Base.resources.BasicSearch',
			"modules.$moduleName.resources.BasicSearch",
			'modules.Base.resources.AdvanceFilter',
			"modules.$moduleName.resources.AdvanceFilter",
			'modules.Base.resources.SearchAdvanceFilter',
			"modules.$moduleName.resources.SearchAdvanceFilter",
			'modules.Base.resources.AdvanceSearch',
			"modules.$moduleName.resources.AdvanceSearch",
			'modules.Settings.DataAccess.resources.SaveResult',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	/**
	 * Function to get the list of Css models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of \App\Modules\Base\Models\CssScript instances
	 */
	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$baseStyleCssPath = \App\Runtime\Vtiger_Theme::getBaseStylePath();
		$baseStyleCssPath = $this->checkAndConvertCssStyles(['~' . $baseStyleCssPath]);
		$headerCssInstances = array_merge($headerCssInstances, $baseStyleCssPath);

		$headerCss = \App\Modules\Base\Models\Link::getAllByType(\vtlib\Link::IGNORE_MODULE, ['HEADERCSS']);
		$selectedThemeCssPath = \App\Runtime\Vtiger_Theme::getThemeStyle();
		$cssScriptModel = new \App\View\Assets\StyleAsset();
		$headerCssInstances[] = $cssScriptModel->set('href', $selectedThemeCssPath);

		foreach ($headerCss as $headerType => $cssLinks) {
			foreach ($cssLinks as $cssLink) {
				if ($this->checkFileUriInRelocatedMouldesFolder($cssLink->linkurl)) {
					$headerCssInstances[] = \App\View\Assets\StyleAsset::getInstanceFromLinkObject($cssLink);
				}
			}
		}
		return $headerCssInstances;
	}

	public function getGuiderModels(\App\Http\Vtiger_Request $request)
	{
		return [];
	}
}
