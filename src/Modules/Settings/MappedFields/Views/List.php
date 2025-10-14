<?php

namespace FreeCRM\Modules\Settings\MappedFields\Views;



/**
 * List View Class for MappedFields Settings
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use FreeCRM\Modules\Settings\MappedFields\Models\Module as Settings_MappedFields_Module_Model;
class List extends \FreeCRM\Modules\Settings\Vtiger\Views\List
{

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		$viewer = $this->getViewer($request);
		$viewer->assign('SUPPORTED_MODULE_MODELS', Settings_MappedFields_Module_Model::getSupportedModules());
		parent::preProcess($request, $display);
	}
}
