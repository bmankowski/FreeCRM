<?php

namespace App\Modules\Settings\AutomaticAssignment\Views;



/**
 * Automatic Assignment List View Class
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

class List extends \App\Modules\Settings\Vtiger\Views\List
{

	/**
	 * Pre-process function
	 * @param \App\Http\Vtiger_Request $request
	 * @param boolean $display
	 */
	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$viewer = $this->getViewer($request);
		$viewer->assign('SUPPORTED_MODULE_MODELS', Settings_AutomaticAssignment_Module_Model::getSupportedModules());
		parent::preProcess($request, $display);
	}
}
