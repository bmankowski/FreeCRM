<?php

namespace App\Modules\KnowledgeBase\Views;

/**
 * @package YetiForce.Views
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */

class Tree  extends \App\Modules\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
	$moduleName = $request->getModule();
	$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
	$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
	$linkModels = $moduleModel->getSideBarLinks($linkParams);
	
	// Process sidebar links to determine active link
	$activeLinkLabel = $this->processSidebarLinks($linkModels, $request);
	
	$viewer = $this->getViewer($request);
	$viewer->assign('MODULE', $moduleName);
	$viewer->assign('QUICK_LINKS', $linkModels);
	$viewer->assign('ACTIVE_SIDEBAR_LINK', $activeLinkLabel);
		$viewer->view('TreeHeader.tpl', $moduleName);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$parentScriptInstances = parent::getFooterScripts($request);
		$scripts = [
			'~libraries/jquery/jstree/jstree.js',
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
