<?php

namespace App\Modules\Settings\WidgetsManagement\Views;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Configuration extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		
		\App\Log\Log::trace(__METHOD__ . ' | Start');
		$currentUser = $request->getUser();
		$sourceModule = $request->get('sourceModule');
		$widgetsManagementModel = new \App\Modules\Settings\WidgetsManagement\Models\Module();
		$dashboardModules = $widgetsManagementModel->getSelectableDashboard();

		if (empty($sourceModule)){
			$sourceModule = 'Home';
		}

		$currentDashboard = $request->get('dashboardId');
		if(empty($currentDashboard)) {
			$currentDashboard = \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultDashboard();
		}
		$viewer = $this->getViewer($request);
		// get widgets list
		$widgets = $dashboardModules[$sourceModule];
		$dashboardStored = $widgetsManagementModel->getDashboardForModule($sourceModule, $currentDashboard);
		$defaultValues = $widgetsManagementModel->getDefaultValues();
		$size = $widgetsManagementModel->getSize();
		$widgetsWithLimit = $widgetsManagementModel->getWidgetsWithLimit();
		$authorization = \App\Modules\Settings\Roles\Models\Record::getAll();
		$bloks = $widgetsManagementModel->getBlocksId($currentDashboard);
		$specialWidgets = \App\Modules\Settings\WidgetsManagement\Models\Module::getSpecialWidgets($sourceModule);
		$filterSelect = $widgetsManagementModel->getFilterSelect();
		$filterSelectDefault = $widgetsManagementModel->getFilterSelectDefault();
		$widgetsWithFilterUsers = $widgetsManagementModel->getWidgetsWithFilterUsers();
		$restrictFilter = $widgetsManagementModel->getRestrictFilter();

		$viewer->assign('CURRENT_DASHBOARD', $currentDashboard);
		$viewer->assign('DASHBOARD_TYPES', \App\Modules\Settings\WidgetsManagement\Models\Module::getDashboardTypes());
		$viewer->assign('FILTER_SELECT', $filterSelect);
		$viewer->assign('FILTER_SELECT_DEFAULT', $filterSelectDefault);
		$viewer->assign('DATE_SELECT_DEFAULT', \App\Modules\Settings\WidgetsManagement\Models\Module::getDateSelectDefault());
		$viewer->assign('WIDGETS_WITH_FILTER_DATE', \App\Modules\Settings\WidgetsManagement\Models\Module::getWidgetsWithDate());
		$viewer->assign('WIDGETS_WITH_FILTER_USERS', $widgetsWithFilterUsers);
		$viewer->assign('ALL_AUTHORIZATION', $authorization);
	$viewer->assign('SELECTED_MODULE_NAME', $sourceModule);
	$viewer->assign('SUPPORTED_MODULES', array_keys($dashboardModules));
	$viewer->assign('DASHBOARD_AUTHORIZATION_BLOCKS', $bloks[$sourceModule] ?? []);
	$viewer->assign('WIDGETS_AUTHORIZATION_INFO', $dashboardStored);
		$viewer->assign('SPECIAL_WIDGETS', $specialWidgets);
		$viewer->assign('CURRENTUSER', $currentUser);
		$viewer->assign('WIDGETS', $widgets);
		$viewer->assign('SIZE', $size);
		$viewer->assign('DEFAULTVALUES', $defaultValues);
		$viewer->assign('TITLE_OF_LIMIT', $widgetsWithLimit);
		$viewer->assign('QUALIFIED_MODULE', $request->getModule(false));
		$viewer->assign('RESTRICT_FILTER', $restrictFilter);
		
		// Prepare WidgetsManagement-specific data for ConfigurationContent template
		$this->prepareWidgetsManagementData($viewer, $widgetsWithFilterDate, $widgetsWithFilterUsers, $restrictFilter);

		// Add AJAX detection for MainLayout conversion
		if ($request->isAjax()) {
			// AJAX request - return content only
			echo $viewer->view('ConfigurationContent.tpl', $request->getModule(false), true);
		} else {
			// Initial page load - return full page with MainLayout
			echo $viewer->view('ConfigurationIndex.tpl', $request->getModule(false), true);
		}
		\App\Log\Log::trace(__METHOD__ . ' | End');
	}
	
	/**
	 * Prepare data for WidgetsManagement ConfigurationContent template
	 * Moves function calls from templates to controller for better MVC separation
	 */
	protected function prepareWidgetsManagementData($viewer, $widgetsWithFilterDate, $widgetsWithFilterUsers, $restrictFilter)
	{
		// Prepare JSON-encoded filter data
		$viewer->assign('FILTER_DATE_JSON', \App\Utils\Json::encode($widgetsWithFilterDate));
		$viewer->assign('FILTER_USERS_JSON', \App\Utils\Json::encode($widgetsWithFilterUsers));
		$viewer->assign('FILTER_RESTRICT_JSON', \App\Utils\Json::encode($restrictFilter));
		
		// Prepare decoded widget data for WidgetConfig template
		$dashboardStored = $viewer->getTemplateVars('WIDGETS_AUTHORIZATION_INFO');
		$widgetInfoDecoded = [];
		$widgetSizeDecoded = [];
		$widgetOwnersDecoded = [];
		
		if ($dashboardStored) {
			foreach ($dashboardStored as $authKey => $widgets) {
				foreach ($widgets as $widgetModel) {
					$widgetId = $widgetModel->get('id');
					// Decode widget data
					$data = $widgetModel->get('data');
					if ($data) {
						$widgetInfoDecoded[$widgetId] = \App\Utils\Json::decode(html_entity_decode($data));
					}
					// Decode widget size
					$size = $widgetModel->get('size');
					if ($size) {
						$widgetSizeDecoded[$widgetId] = \App\Utils\Json::decode(html_entity_decode($size));
					}
					// Decode widget owners
					$owners = $widgetModel->get('owners');
					if ($owners) {
						$widgetOwnersDecoded[$widgetId] = \App\Utils\Json::decode(html_entity_decode($owners));
					}
				}
			}
		}
		$viewer->assign('WIDGET_INFO_DECODED', $widgetInfoDecoded);
		$viewer->assign('WIDGET_SIZE_DECODED', $widgetSizeDecoded);
		$viewer->assign('WIDGET_OWNERS_DECODED', $widgetOwnersDecoded);
	}
}
