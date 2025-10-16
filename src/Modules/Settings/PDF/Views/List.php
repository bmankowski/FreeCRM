<?php

namespace FreeCRM\Modules\Settings\PDF\Views;



/**
 * List View Class for PDF Settings
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class List extends \FreeCRM\Modules\Settings\Vtiger\Views\List
{

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		$viewer = $this->getViewer($request);
		$viewer->assign('SUPPORTED_MODULE_MODELS', \FreeCRM\Modules\Settings\PDF\Models\Module::getSupportedModules());
		parent::preProcess($request, $display);
	}
}
