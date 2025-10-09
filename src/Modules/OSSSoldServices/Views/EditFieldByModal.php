<?php

namespace FreeCRM\Modules\OSSSoldServices\Views;

/**
 * EditFieldByModal View Class for OSSSoldServices
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use FreeCRM\Http\Vtiger_Request;
class EditFieldByModal extends View
{

	public function getModalScripts(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$viewName = $request->get('view');

		$scripts = [
			"modules.Vtiger.resources.$viewName",
			"modules.Assets.resources.$viewName",
			"modules.$moduleName.resources.$viewName"
		];

		$scriptInstances = $this->checkAndConvertJsScripts($scripts);
		return $scriptInstances;
	}
}
