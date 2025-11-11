<?php

namespace App\Modules\Products\Views;

/**
 * Products TreeView View Class
 * @package YetiForce.TreeView
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class TreeRecords  extends \App\Modules\Base\Views\Index
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		
		// Prepare tree records data
		$branches = $request->get('branches');
		$filter = $request->get('filter');
		$category = $request->get('category');
		if (empty($branches) && empty($category)) {
			return;
		}
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$baseModuleName = 'Accounts';

		$multiReferenceFirld = \App\Modules\Base\UiTypes\MultiReferenceValue::getFieldsByModules($baseModuleName, $moduleName);
		$multiReferenceFirld = reset($multiReferenceFirld);
		if (count($multiReferenceFirld) === 0) {
			return;
		}

		$pagingModel = new \App\Modules\Base\Models\Paging();
		$pagingModel->set('limit', 'no_limit');
		$listViewModel = \App\Modules\Base\Models\ListView::getInstance($baseModuleName, $filter);
		$queryGenerator = $listViewModel->get('query_generator');
		if (!empty($branches)) {
			$queryGenerator->addCondition($multiReferenceFirld['columnname'], implode(',', $branches), 'c');
		}
		if (!empty($category)) {
			$query = (new \App\Db\Query())
				->select(['crmid'])
				->from('u_#__crmentity_rel_tree')
				->where(['module' => \App\Utils\ModuleUtils::getModuleId($baseModuleName), 'relmodule' => \App\Utils\ModuleUtils::getModuleId($moduleName), 'tree' => $category]);
			$queryGenerator->addNativeCondition(['in', 'crmid', $query], false);
		}
		$listViewModel->set('query_generator', $queryGenerator);
		$listEntries = $listViewModel->getListViewEntries($pagingModel);
		if (count($listEntries) === 0) {
			return;
		}
		$listHeaders = $listViewModel->getListViewHeaders();

		$viewer->assign('SELECTABLE_CATEGORY', \App\AppConfig::relation('SELECTABLE_CATEGORY') ? 1 : 0);
		$viewer->assign('CUSTOM_VIEWS', \App\Modules\CustomView\Models\Record::getAllByGroup($baseModuleName));
		$viewer->assign('ENTRIES', $listEntries);
		$viewer->assign('HEADERS', $listHeaders);
		$viewer->assign('MODULE', $baseModuleName);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		// Data already assigned in preProcess, just render
		$viewer->view('TreeRecords.tpl', $moduleName);
	}
}
