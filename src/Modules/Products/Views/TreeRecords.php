<?php

namespace App\Modules\Products\Views;

/**
 * Products TreeView View Class
 * @package YetiForce.TreeView
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class TreeRecords extends \Vtiger_Index_View
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request);
		$viewer = $this->getViewer($request);
		$viewer->assign('SELECTABLE_CATEGORY', \App\AppConfig::relation('SELECTABLE_CATEGORY') ? 1 : 0);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$branches = $request->get('branches');
		$filter = $request->get('filter');
		$category = $request->get('category');
		if (empty($branches) && empty($category)) {
			return;
		}
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$baseModuleName = 'Accounts';

		$multiReferenceFirld = \App\Modules\Vtiger\UiTypes\MultiReferenceValue::getFieldsByModules($baseModuleName, $moduleName);
		$multiReferenceFirld = reset($multiReferenceFirld);
		if (count($multiReferenceFirld) === 0) {
			return;
		}

		$pagingModel = new \App\Modules\Vtiger\Models\Paging();
		$pagingModel->set('limit', 'no_limit');
		$listViewModel = \App\Modules\Vtiger\Models\ListView::getInstance($baseModuleName, $filter);
		$queryGenerator = $listViewModel->get('query_generator');
		if (!empty($branches)) {
			$queryGenerator->addCondition($multiReferenceFirld['columnname'], implode(',', $branches), 'c');
		}
		if (!empty($category)) {
			$query = (new \App\Db\Query())
				->select(['crmid'])
				->from('u_#__crmentity_rel_tree')
				->where(['module' => \App\Module::getModuleId($baseModuleName), 'relmodule' => \App\Module::getModuleId($moduleName), 'tree' => $category]);
			$queryGenerator->addNativeCondition(['in', 'crmid', $query], false);
		}
		$listViewModel->set('query_generator', $queryGenerator);
		$listEntries = $listViewModel->getListViewEntries($pagingModel);
		if (count($listEntries) === 0) {
			return;
		}
		$listHeaders = $listViewModel->getListViewHeaders();

		$viewer->assign('ENTRIES', $listEntries);
		$viewer->assign('HEADERS', $listHeaders);
		$viewer->assign('MODULE', $baseModuleName);
		$viewer->view('TreeRecords.tpl', $moduleName);
	}

	public function postProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$viewer = $this->getViewer($request);
		$baseModuleName = 'Accounts';
		$viewer->assign('CUSTOM_VIEWS', \App\Modules\CustomView\Models\Record::getAllByGroup($baseModuleName));
		$viewer->view('TreeRecordsPostProcess.tpl', $request->getModule());
		parent::postProcess($request, false);
	}
}
