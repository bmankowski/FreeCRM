<?php

namespace App\Modules\Settings\Base\Views;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */


class Index extends \App\Modules\Base\Views\Basic
{

	public function __construct()
	{
		\App\Modules\Settings\Base\Models\Tracker::addBasic('view');
		parent::__construct();
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		if (!$currentUserModel->isAdminUser()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		
		// Assign Settings-specific menu and selected page
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$selectedMenuId = $request->get('block');
		$fieldId = $request->get('fieldid');
		$settingsModel = \App\Modules\Settings\Base\Models\Module::getInstance();
		$menuModels = $settingsModel->getMenus();
		$menu = $settingsModel->prepareMenuToDisplay($menuModels, $moduleName, $selectedMenuId, $fieldId);
		if ($settingsModel->has('selected')) {
			$viewer->assign('SELECTED_PAGE', $settingsModel->get('selected'));
		}
		$viewer->assign('MENUS', $menu);
		
		// MainLayout handles rendering, no separate preProcess template needed
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		// Redirect to Dashboard for Settings homepage
		header('Location: index.php?module=Dashboard&parent=Settings&view=Index');
		exit();
	}

	protected function getMenu()
	{
		// Settings pages use their own menu system set in preProcess
		// Return empty array to prevent parent from overriding
		return [];
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
