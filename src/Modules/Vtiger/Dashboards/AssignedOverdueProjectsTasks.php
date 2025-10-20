<?php

namespace App\Modules\Vtiger\Dashboards;

/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************************************************************** */

use App\Http\Vtiger_Request;

class AssignedOverdueProjectsTasks  extends \App\Modules\Vtiger\Views\Index
{

	public function process(Vtiger_Request $request)
	{
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$data = $request->getAll();

		$page = $request->get('page');
		$linkId = $request->get('linkid');

		$widget = \App\Modules\Vtiger\Models\Widget::getInstance($linkId, $currentUser->getId());
		if (!$request->has('owner'))
			$owner = \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultUserId($widget);
		else
			$owner = $request->get('owner');

		$pagingModel = new \App\Modules\Vtiger\Models\Paging();
		$pagingModel->set('page', $page);
		$pagingModel->set('limit', (int) $widget->get('limit'));

		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$projectsTasks = ($owner === false) ? [] : $moduleModel->getAssignedProjectsTasks('overdue', $pagingModel, $owner);
		$currentDate = date('Y-m-d');

		$viewer->assign('SOURCE_MODULE', 'ProjectTask');
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('PROJECTSTASKS', $projectsTasks);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('CURRENTUSER', $currentUser);
		$title_max_length = vglobal('title_max_length');
		$href_max_length = vglobal('href_max_length');
		$viewer->assign('NAMELENGTH', $title_max_length);
		$viewer->assign('HREFNAMELENGTH', $href_max_length);
		$viewer->assign('OWNER', $owner);
		$viewer->assign('NODATAMSGLABLE', 'LBL_NO_OVERDUE_ACTIVITIES');
		$viewer->assign('DATA', $data);
		$viewer->assign('USER_CONDITIONS', ['condition' => ['<', 'targetenddate', $currentDate]]);
		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/AssignedProjectsTasksContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/AssignedProjectsTasks.tpl', $moduleName);
		}
	}
}
