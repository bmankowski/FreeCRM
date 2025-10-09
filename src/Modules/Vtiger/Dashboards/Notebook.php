<?php

namespace FreeCRM\Modules\Vtiger\Dashboards;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

use FreeCRM\Http\Vtiger_Request;

class Notebook extends View
{

	public function process(Vtiger_Request $request, $widget = NULL)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		// Initialize Widget to the right-state of information
		if ($widget && !$request->has('widgetid')) {
			$widgetId = $widget->get('id');
		} else {
			$widgetId = $request->get('widgetid');
		}

		$widget = Vtiger_Notebook_Model::getUserInstance($widgetId);

		$mode = $request->get('mode');
		if ($mode == 'save') {
			$widget->save($request);
		}
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);


		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/NotebookContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/Notebook.tpl', $moduleName);
		}
	}
}
