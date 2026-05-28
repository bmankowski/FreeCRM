<?php

namespace App\Modules\Settings\Inventory\Views;
use App\HttpVtiger_Request;
use App\Modules\Settings\InventoryViews\CreditLimits;



/**
 * @package YetiForce.views
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class ModalAjax extends \App\Modules\Settings\Inventory\Views\CreditLimits
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$id = $request->get('id');
		$type = $request->get('type');

		if (empty($id)) {
			$recordModel = new \App\Modules\Settings\Inventory\Models\Record();
		} else {
			$recordModel = \App\Modules\Settings\Inventory\Models\Record::getInstanceById($id, $type);
		}

		$viewer->assign('PAGE_LABELS', $this->getPageLabels($request));
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('TYPE', $type);
		$viewer->assign('CURRENCY', \App\Modules\Base\Helpers\Util::getBaseCurrency());
		echo $viewer->view('Modal.tpl', $qualifiedModuleName, true);
	}
}
