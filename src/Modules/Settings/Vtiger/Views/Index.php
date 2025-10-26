<?php

namespace App\Modules\Settings\Vtiger\Views;
use App\Modules\Settings\Vtiger\Models\Tracker;
use App\Modules\Settings\Vtiger\Models\MenuItem;
use App\Modules\Settings\GithubModels\Issues;
use App\Modules\Settings\ModuleManager\Models\Module;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

use App\Http\App\Http\Vtiger_Session;

class Index extends \App\Modules\Vtiger\Views\Basic
{

	public function __construct()
	{
		\App\Modules\Settings\Vtiger\Models\Tracker::addBasic('view');
		parent::__construct();
		$this->exposeMethod('DonateUs');
		$this->exposeMethod('index');
		$this->exposeMethod('github');
		$this->exposeMethod('systemWarnings');
		$this->exposeMethod('getWarningsList');
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		if (!$currentUserModel->isAdminUser()) {
			throw new \Exception\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Build breadcrumbs for Settings pages
	 * @param \App\Http\Vtiger_Request $request
	 * @return array
	 */
	protected function buildBreadcrumbs(\App\Http\Vtiger_Request $request)
	{
		$breadcrumbs = [];
		$pageTitle = $this->getBreadcrumbTitle($request);
		$moduleName = $request->getModule();
		$view = $request->get('view');
		$qualifiedModuleName = $request->getModule(false);
		
		// Settings home breadcrumb
		$breadcrumbs[] = [
			'name' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_SETTINGS', $qualifiedModuleName),
			'url' => 'index.php?module=Vtiger&parent=Settings&view=Index',
		];
		
		// Add specific settings module breadcrumb if not on Settings home
		if ($moduleName !== 'Vtiger' || $view !== 'Index') {
			$fieldId = $request->get('fieldid');
			$menu = \App\Modules\Settings\Vtiger\Models\MenuItem::getAll();
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
		
		return $breadcrumbs;
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		$this->preProcessSettings($request);
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		$this->postProcessSettings($request);
		parent::postProcess($request);
	}

	/**
	 * Pre process settings
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function preProcessSettings(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$selectedMenuId = $request->get('block');
		$fieldId = $request->get('fieldid');
		$settingsModel = \App\Modules\Settings\Vtiger\Models\Module::getInstance();
		$menuModels = $settingsModel->getMenus();
		$menu = $settingsModel->prepareMenuToDisplay($menuModels, $moduleName, $selectedMenuId, $fieldId);
		if ($settingsModel->has('selected')) {
			$viewer->assign('SELECTED_PAGE', $settingsModel->get('selected'));
		}
		$viewer->assign('MENUS', $menu);
		$viewer->view('SettingsMenuStart.tpl', $qualifiedModuleName);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
		$this->getViewer($request)->view('SettingsIndexHeader.tpl', $request->getModule(false));
	}

	public function postProcessSettings(\App\Http\Vtiger_Request $request)
	{
		$this->getViewer($request)->view('SettingsMenuEnd.tpl', $request->getModule(false));
	}

	/**
	 * Index
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function index(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
		$usersCount = \App\Modules\Users\Models\Record::getCount(true);
		$allWorkflows = \App\Modules\Settings\Workflows\Models\Record::getAllAmountWorkflowsAmount();
		$activeModules = \App\Modules\Settings\ModuleManager\Models\Module::getModulesCount(true);
		$pinnedSettingsShortcuts = \App\Modules\Settings\Vtiger\Models\MenuItem::getPinnedItems();
		$warnings = \App\SystemWarnings::getWarnings('all');

		$viewer->assign('WARNINGS_COUNT', count($warnings));
		$viewer->assign('WARNINGS', !\App\Http\Vtiger_Session::has('SystemWarnings') ? $warnings : []);
		$viewer->assign('USERS_COUNT', $usersCount);
		$viewer->assign('ALL_WORKFLOWS', $allWorkflows);
		$viewer->assign('ACTIVE_MODULES', $activeModules);
		$viewer->assign('SETTINGS_SHORTCUTS', $pinnedSettingsShortcuts);
		$viewer->view('Index.tpl', $qualifiedModuleName);
	}

	public function github(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = 'Settings:Github';
		$clientModel = \App\Modules\Settings\Github\Models\Client::getInstance();
		$isAuthor = $request->get('author');
		$isAuthor = $isAuthor == 'true' ? true : false;
		$pageNumber = $request->get('page');
		if (empty($pageNumber)) {
			$pageNumber = 1;
		}

		$state = empty($request->get('state')) ? 'open' : $request->get('state');
		$issues = $clientModel->getAllIssues($pageNumber, $state, $isAuthor);
		$pagingModel = new \App\Modules\Vtiger\Models\Paging();
		$pagingModel->set('page', $pageNumber);
		$pagingModel->set('totalCount', \App\Modules\Settings\Github\Models\Issues::$totalCount);

		$pageCount = $pagingModel->getPageCount();
		$startPaginFrom = $pagingModel->getStartPagingFrom();

		$viewer->assign('IS_AUTHOR', $isAuthor);
		$viewer->assign('PAGE_NUMBER', $pageNumber);
		$viewer->assign('ISSUES_STATE', $state);
		$viewer->assign('PAGE_COUNT', $pageCount);
		$viewer->assign('LISTVIEW_ENTRIES_COUNT', false);
		$viewer->assign('LISTVIEW_COUNT', \App\Modules\Settings\Github\Models\Issues::$totalCount);
		$viewer->assign('START_PAGIN_FROM', $startPaginFrom);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('MODULE', $qualifiedModuleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('GITHUB_ISSUES', $issues);
		$viewer->assign('GITHUB_CLIENT_MODEL', $clientModel);
		$viewer->view('Github.tpl', $qualifiedModuleName);
	}

	public function DonateUs(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
		$viewer->view('DonateUs.tpl', $qualifiedModuleName);
	}

	/**
	 * Displays warnings system
	 * 
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function systemWarnings(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);

		$folders = array_values(\App\SystemWarnings::getFolders());
		$viewer->assign('MODULE', $qualifiedModuleName);
		$viewer->assign('FOLDERS', \App\Json::encode($folders));
		$viewer->view('SystemWarnings.tpl', $qualifiedModuleName);
	}

	/**
	 * Displays a list of system warnings
	 * 
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function getWarningsList(\App\Http\Vtiger_Request $request)
	{
		$folder = $request->get('folder');
		$active = $request->getBoolean('active');
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);

		$list = \App\SystemWarnings::getWarnings($folder, $active);
		$viewer->assign('MODULE', $qualifiedModuleName);
		$viewer->assign('WARNINGS_LIST', $list);
		$viewer->view('SystemWarningsList.tpl', $qualifiedModuleName);
	}

	protected function getMenu()
	{
		return [];
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of ScriptAsset instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Vtiger.resources.Vtiger',
			'libraries.jquery.ckeditor.ckeditor',
			'libraries.jquery.ckeditor.adapters.jquery',
			'libraries.jquery.jstree.jstree',
			'~libraries/jquery/datatables/media/js/jquery.dataTables.js',
			'~libraries/jquery/datatables/plugins/integration/bootstrap/3/dataTables.bootstrap.js',
			'modules.Vtiger.resources.CkEditor',
			'modules.Settings.Vtiger.resources.Vtiger',
			'modules.Settings.Vtiger.resources.Edit',
			"modules.Settings.$moduleName.resources.$moduleName",
			'modules.Settings.Vtiger.resources.Index',
			"modules.Settings.$moduleName.resources.Index",
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	/**
	 * Retrieves css styles that need to loaded in the page
	 * @param \App\Http\Vtiger_Request $request - request model
	 * @return <array> - array of StyleAsset
	 */
	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'libraries.jquery.jstree.themes.proton.style',
			'~libraries/jquery/datatables/media/css/jquery.dataTables_themeroller.css',
			'~libraries/jquery/datatables/plugins/integration/bootstrap/3/dataTables.bootstrap.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($cssInstances, $headerCssInstances);
	}

	public static function getSelectedFieldFromModule($menuModels, $moduleName)
	{
		if ($menuModels) {
			foreach ($menuModels as $menuModel) {
				$menuItems = $menuModel->getMenuItems();
				foreach ($menuItems as $item) {
					$linkTo = $item->getUrl();
					if (stripos($linkTo, '&module=' . $moduleName) !== false || stripos($linkTo, '?module=' . $moduleName) !== false) {
						return $item;
					}
				}
			}
		}
		return false;
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateReadAccess();
	}
}
