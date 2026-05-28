<?php

namespace App\Modules\Settings\MappedFields\Views;



/**
 * List View Class for MappedFields Settings
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class ListView extends \App\Modules\Settings\Base\Views\ListView
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$viewer = $this->getViewer($request);
		$viewer->assign('SUPPORTED_MODULE_MODELS', \App\Modules\Settings\MappedFields\Models\Module::getSupportedModules());
		parent::preProcess($request, $display);
	}
}
