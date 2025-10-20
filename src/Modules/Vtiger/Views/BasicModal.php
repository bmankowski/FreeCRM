<?php

namespace App\Modules\Vtiger\Views;

/**
 * Basic Modal Class
 * @package YetiForce.Modal
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class BasicModal  extends \App\Modules\Vtiger\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPrivilegesModel->hasModulePermission($request->getModule())) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function getSize(\App\Http\Vtiger_Request $request)
	{
		return '';
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$moduleName = $request->getModule();
		$viewName = $request->get('view');
		echo '<div class="modal fade modal' . $moduleName . '' . $viewName . '" id="modal' . $viewName . '"><div class="modal-dialog ' . $this->getSize($request) . '"><div class="modal-content">';
		foreach ($this->getModalCss($request) as $style) {
			echo '<link rel="stylesheet" href="' . $style->getHref() . '">';
		}
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		foreach ($this->getModalScripts($request) as $script) {
			echo '<script type="' . $script->getType() . '" src="' . $script->getSrc() . '"></script>';
		}
		echo '</div></div></div>';
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$this->preProcess($request);
		//Content
		$this->postProcess($request);
	}

	public function getModalScripts(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$viewName = $request->get('view');

		$scripts = array(
			"modules.Vtiger.resources.$viewName",
			"modules.$moduleName.resources.$viewName"
		);

		$scriptInstances = $this->checkAndConvertJsScripts($scripts);
		return $scriptInstances;
	}

	public function getModalCss(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$viewName = $request->get('view');
		$cssFileNames = [
			"modules.$moduleName.$viewName",
			"modules.Vtiger.$viewName",
		];
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = $cssInstances;
		return $headerCssInstances;
	}
}
