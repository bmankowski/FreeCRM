<?php

namespace FreeCRM\Modules\Settings\AutomaticAssignment\Views;



/**
 * Automatic Assignment List View Class
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use FreeCRM\Modules\Settings\AutomaticAssignment\Models\Module as Settings_AutomaticAssignment_Module_Model;
class List extends \FreeCRM\Modules\Settings\Vtiger\Views\List
{

	/**
	 * Pre-process function
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 * @param boolean $display
	 */
	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		$viewer = $this->getViewer($request);
		$viewer->assign('SUPPORTED_MODULE_MODELS', Settings_AutomaticAssignment_Module_Model::getSupportedModules());
		parent::preProcess($request, $display);
	}
}
