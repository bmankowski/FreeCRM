<?php

namespace App\Modules\Assets\Dashboards;

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

class ExpiringSoldProducts  extends \App\Modules\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$widget = \App\Modules\Base\Models\Widget::getInstance($request->get('linkid'), $currentUser->getId());
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('RELATED_MODULE', 'Assets');
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('DATA', self::getData($request, $widget));
		//Include special script and css needed for this widget
		$viewer->assign('CURRENTUSER', $currentUser);
		if (!$request->isEmpty('content')) {
			$viewer->view('dashboards/ExpiringSoldProductsContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/ExpiringSoldProducts.tpl', $moduleName);
		}
	}

	public static function getData(\App\Http\Vtiger_Request $request, $widget)
	{
		$fields = ['id', 'assetname', 'dateinservice', 'parent_id'];
		$limit = 10;
		if (!empty($widget->get('limit'))) {
			$limit = $widget->get('limit');
		}
		$queryGenerator = new \App\QueryGenerator('Assets');
		$queryGenerator->setFields($fields);
		$query = $queryGenerator->createQuery();
		$showtype = $request->get('showtype');
		if ($showtype === 'common') {
			$subQuery = (new \App\Db\Query())->select('crmid')->from('u_#__crmentity_showners')->where(['userid' => \App\Modules\Users\Models\Record::getCurrentUserId()])->distinct('crmid');
			$query->andWhere(['in', 'vtiger_crmentity.smownerid', $subQuery]);
		} else {
			$query->andWhere(['vtiger_crmentity.smownerid' => \App\Modules\Users\Models\Record::getCurrentUserId()]);
		}
		$query->orderBy('vtiger_assets.dateinservice');
		$query->limit($limit);
		$data = $query->all();
		// Add module type for each row with parent_id
		foreach ($data as &$row) {
			if (!empty($row['parent_id'])) {
				$row['parent_module'] = \App\Record::getType($row['parent_id']);
			}
		}
		return $data;
	}
}
