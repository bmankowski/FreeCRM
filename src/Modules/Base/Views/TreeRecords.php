<?php

namespace App\Modules\Base\Views;

/**
 * Basic TreeView View Class
 * @package YetiForce.TreeView
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class TreeRecords  extends \App\Modules\Base\Views\Index
{

	public function getBreadcrumbTitle(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$treeViewModel = \App\Modules\Base\Models\TreeView::getInstance($moduleModel);
		$pageTitle = \App\Runtime\Vtiger_Language_Handler::translate($treeViewModel->getName(), $moduleName);
		return $pageTitle;
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		// MainLayout handles rendering, no separate preProcess/postProcess templates needed
	}

	public function postProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		// MainLayout handles footer rendering, no separate postProcess template needed
		parent::postProcess($request);
	}

	protected function postProcessDisplay(\App\Http\Vtiger_Request $request)
	{
		// No longer needed - MainLayout handles display
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$branches = $request->get('branches');
		$filter = $request->get('filter');
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$treeViewModel = \App\Modules\Base\Models\TreeView::getInstance($moduleModel);

		// Assign tree data needed for initial page load
		$treeList = $treeViewModel->getTreeList();
		$viewer->assign('TREE_LIST', \App\Json::encode($treeList));
		$viewer->assign('SELECTABLE_CATEGORY', 0);
		$viewer->assign('CUSTOM_VIEWS', \App\Modules\CustomView\Models\Record::getAllByGroup($moduleName));
		
		if (!empty($branches)) {
			// AJAX request for tree branch data - return partial content
			$pagingModel = new \App\Modules\Base\Models\Paging();
			$pagingModel->set('limit', 'no_limit');
			$listViewModel = \App\Modules\Base\Models\ListView::getInstance($moduleName, $filter);
			$listViewModel->set('search_params', $treeViewModel->getSearchParams($branches));

			$listEntries = $listViewModel->getListViewEntries($pagingModel);
			if (count($listEntries) === 0) {
				return;
			}
			$listHeaders = $listViewModel->getListViewHeaders();

			$viewer->assign('ENTRIES', $listEntries);
			$viewer->assign('HEADERS', $listHeaders);
			$viewer->assign('MODULE', $moduleName);
		}
		
		$viewer->view('TreeRecords.tpl', $moduleName);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$parentScriptInstances = parent::getFooterScripts($request);

		$scripts = [
			'~libraries/jquery/jstree/jstree.js',
			'~libraries/jquery/jstree/jstree.category.js',
			'~libraries/jquery/jstree/jstree.checkbox.js',
			'~libraries/jquery/datatables/media/js/jquery.dataTables.js',
			'~libraries/jquery/datatables/plugins/integration/bootstrap/3/dataTables.bootstrap.js',
		];
		$viewInstances = $this->checkAndConvertJsScripts($scripts);
		$scriptInstances = array_merge($parentScriptInstances, $viewInstances);
		return $scriptInstances;
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$parentCssInstances = parent::getHeaderCss($request);
		$cssFileNames = [
			'~libraries/jquery/jstree/themes/proton/style.css',
			'~libraries/jquery/datatables/media/css/jquery.dataTables_themeroller.css',
			'~libraries/jquery/datatables/plugins/integration/bootstrap/3/dataTables.bootstrap.css',
		];
		$modalInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$cssInstances = array_merge($parentCssInstances, $modalInstances);
		return $cssInstances;
	}
}
