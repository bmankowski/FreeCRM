<?php

namespace App\Modules\Settings\Users\Views;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Detail extends \App\Modules\Users\Views\PreferenceDetail {

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		$record = $request->get('record');
		if ($currentUserModel->isAdminUser() === true || ($currentUserModel->get('id') == $record && \App\AppConfig::security('SHOW_MY_PREFERENCES'))) {
			return true;
		} else {
			throw new \App\Exceptions\AppException('LBL_PERMISSION_DENIED');
		}
	}

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
			'url' => 'index.php?module=Dashboard&parent=Settings&view=Index',
		];
		
		// Add specific settings module breadcrumb
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
			} elseif ($view == 'Edit' && $request->get('record') == '') {
				$breadcrumbs[] = ['name' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_CREATE', $qualifiedModuleName)];
			} elseif ($view != '' && $view != 'List') {
				$breadcrumbs[] = ['name' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_' . strtoupper($view), $qualifiedModuleName)];
			}
			if ($request->get('record') != '') {
				$recordLabel = \App\Fields\Owner::getUserLabel($request->get('record'));
				if ($recordLabel != '') {
					$breadcrumbs[] = ['name' => $recordLabel];
				}
			}
		}
		
		return $breadcrumbs;
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		// Assign breadcrumbs for Settings pages
		$viewer = $this->getViewer($request);
		$viewer->assign('BREADCRUMBS', $this->buildBreadcrumbs($request));
		// MainLayout handles rendering, no separate preProcess template needed
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Settings.Vtiger.resources.Index'
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	/**
	 * Function to get Ajax is enabled or not
	 * @param \App\Modules\Base\Models\Record record model
	 * @return <boolean> true/false
	 */
	public function isAjaxEnabled($recordModel)
	{
		if ($recordModel->get('status') != 'Active') {
			return false;
		}
		return $recordModel->isEditable();
	}
}
