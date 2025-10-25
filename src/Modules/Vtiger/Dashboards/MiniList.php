<?php

namespace App\Modules\Vtiger\Dashboards;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

use App\Http\Vtiger_Request;

class MiniList  extends \App\Modules\Vtiger\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request, $widget = NULL)
	{
		$currentUser = $request->getUser();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$data = $request->getAll();

		// Initialize Widget to the right-state of information
		if ($widget && !$request->has('widgetid')) {
			$widgetId = $widget->get('id');
		} else {
			$widgetId = $request->get('widgetid');
		}

		$widget = \App\Modules\Vtiger\Models\Widget::getInstanceWithWidgetId($widgetId, $currentUser->getId());
		if (!$request->has('owner'))
			$owner = \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultUserId($widget);
		else
			$owner = $request->get('owner');

		$minilistWidgetModel = new \App\Modules\Vtiger\Models\MiniList();
		$minilistWidgetModel->setWidgetModel($widget);

		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('OWNER', $owner);
		$viewer->assign('CURRENTUSER', $currentUser);
		$viewer->assign('MINILIST_WIDGET_MODEL', $minilistWidgetModel);
		$viewer->assign('BASE_MODULE', $minilistWidgetModel->getTargetModule());
		$viewer->assign('SCRIPTS', $this->getFooterScripts($request));
		$viewer->assign('DATA', $data);

		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/MiniListContents.tpl', $moduleName);
			$viewer->view('dashboards/MiniListFooter.tpl', $moduleName);
		} else {
			$widget->set('title', $minilistWidgetModel->getTitle());
			$viewer->view('dashboards/MiniList.tpl', $moduleName);
		}
	}
}
