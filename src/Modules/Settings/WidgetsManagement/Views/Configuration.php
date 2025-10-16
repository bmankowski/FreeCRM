<?php

namespace FreeCRM\Modules\Settings\WidgetsManagement\Views;
use FreeCRM\Modules\Settings\WidgetsManagement\Models\Module as Settings_WidgetsManagement_Module_Model;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Configuration extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		
		\App\Log::trace(__METHOD__ . ' | Start');
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		$sourceModule = $request->get('sourceModule');
		$widgetsManagementModel = new \FreeCRM\Modules\Settings\WidgetsManagement\Models\Module();
		$dashboardModules = $widgetsManagementModel->getSelectableDashboard();

		if (empty($sourceModule)){
			$sourceModule = 'Home';
		}

		$currentDashboard = $request->get('dashboardId');
		if(empty($currentDashboard)) {
			$currentDashboard = \FreeCRM\Modules\Settings\WidgetsManagement\Models\Module::getDefaultDashboard();
		}
		$viewer = $this->getViewer($request);
		// get widgets list
		$widgets = $dashboardModules[$sourceModule];
		$dashboardStored = $widgetsManagementModel->getDashboardForModule($sourceModule, $currentDashboard);
		$defaultValues = $widgetsManagementModel->getDefaultValues();
		$size = $widgetsManagementModel->getSize();
		$widgetsWithLimit = $widgetsManagementModel->getWidgetsWithLimit();
		$authorization = \FreeCRM\Modules\Settings\Roles\Models\Record::getAll();
		$bloks = $widgetsManagementModel->getBlocksId($currentDashboard);
		$specialWidgets = \FreeCRM\Modules\Settings\WidgetsManagement\Models\Module::getSpecialWidgets($sourceModule);
		$filterSelect = $widgetsManagementModel->getFilterSelect();
		$filterSelectDefault = $widgetsManagementModel->getFilterSelectDefault();
		$widgetsWithFilterUsers = $widgetsManagementModel->getWidgetsWithFilterUsers();
		$restrictFilter = $widgetsManagementModel->getRestrictFilter();

		$viewer->assign('CURRENT_DASHBOARD', $currentDashboard);
		$viewer->assign('DASHBOARD_TYPES', \FreeCRM\Modules\Settings\WidgetsManagement\Models\Module::getDashboardTypes());
		$viewer->assign('FILTER_SELECT', $filterSelect);
		$viewer->assign('FILTER_SELECT_DEFAULT', $filterSelectDefault);
		$viewer->assign('DATE_SELECT_DEFAULT', \FreeCRM\Modules\Settings\WidgetsManagement\Models\Module::getDateSelectDefault());
		$viewer->assign('WIDGETS_WITH_FILTER_DATE', \FreeCRM\Modules\Settings\WidgetsManagement\Models\Module::getWidgetsWithDate());
		$viewer->assign('WIDGETS_WITH_FILTER_USERS', $widgetsWithFilterUsers);
		$viewer->assign('ALL_AUTHORIZATION', $authorization);
		$viewer->assign('SELECTED_MODULE_NAME', $sourceModule);
		$viewer->assign('SUPPORTED_MODULES', array_keys($dashboardModules));
		$viewer->assign('DASHBOARD_AUTHORIZATION_BLOCKS', $bloks[$sourceModule]);
		$viewer->assign('WIDGETS_AUTHORIZATION_INFO', $dashboardStored);
		$viewer->assign('SPECIAL_WIDGETS', $specialWidgets);
		$viewer->assign('CURRENTUSER', $currentUser);
		$viewer->assign('WIDGETS', $widgets);
		$viewer->assign('SIZE', $size);
		$viewer->assign('DEFAULTVALUES', $defaultValues);
		$viewer->assign('TITLE_OF_LIMIT', $widgetsWithLimit);
		$viewer->assign('QUALIFIED_MODULE', $request->getModule(false));
		$viewer->assign('RESTRICT_FILTER', $restrictFilter);

		echo $viewer->view('Configuration.tpl', $request->getModule(false), true);
		\App\Log::trace(__METHOD__ . ' | End');
	}
}
