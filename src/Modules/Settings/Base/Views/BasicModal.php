<?php

namespace App\Modules\Settings\Base\Views;



/**
 * Basic Modal Class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class BasicModal extends \App\Modules\Settings\Base\Views\IndexAjax
{

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

	public function getSize(\App\Http\Vtiger_Request $request)
	{
		return '';
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
			"modules.Settings.$moduleName.resources.$viewName",
			"modules.Settings.Vtiger.resources.$viewName"
		);

		$scriptInstances = $this->checkAndConvertJsScripts($scripts);
		return $scriptInstances;
	}

	public function getModalCss(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$viewName = $request->get('view');
		$cssFileNames = [
			"modules.Settings.$moduleName.$viewName",
			"modules.Settings.Vtiger.$viewName",
		];
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = $cssInstances;
		return $headerCssInstances;
	}
}
