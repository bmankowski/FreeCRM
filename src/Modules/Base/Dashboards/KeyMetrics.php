<?php

namespace App\Modules\Base\Dashboards;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

use App\Http\Vtiger_Request;

class KeyMetrics  extends \App\Modules\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$linkId = $request->get('linkid');
		$data = $request->getAll();

		$widget = \App\Modules\Base\Models\Widget::getInstance($linkId, $currentUser->getId());

		$keyMetrics = $this->getKeyMetricsWithCount();

		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('KEYMETRICS', $keyMetrics);
		$viewer->assign('DATA', $data);

		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/KeyMetricsContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/KeyMetrics.tpl', $moduleName);
		}
	}

	// NOTE: Move this function to appropriate model.
	protected function getKeyMetricsWithCount()
	{
		// Current user is already available via getCurrentUserModel()
		$metriclists = getMetricList();
		foreach ($metriclists as $key => &$metriclist) {
			$queryGenerator = new \App\QueryField\QueryGenerator($metriclist['module']);
			$queryGenerator->initForCustomViewById($metriclist['id']);
			$metriclists[$key]['count'] = $queryGenerator->createQuery()->count();
		}
		return $metriclists;
	}
}
