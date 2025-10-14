<?php

namespace FreeCRM\Modules\Settings\WidgetsManagement\Views;



/**
 * Form to add widget
 * @package YetiForce.view
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class AddRss extends \FreeCRM\Modules\Settings\Vtiger\Views\BasicModal
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule(false);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_NAME', $request->getModule());
		$viewer->view('AddRss.tpl', $moduleName);
	}
}
