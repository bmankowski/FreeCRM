<?php

namespace FreeCRM\Modules\OSSMailView\Views;

/**
 * Change type action class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use FreeCRM\Http\Vtiger_Request;
class ChangeType extends \Vtiger_Index_View
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$module = $request->getModule();
		$type_list = \FreeCRM\Modules\OSSMailView\Models\Record::getMailType();
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $module);
		$viewer->assign('TYPE_LIST', $type_list);
		$viewer->view('ChangeType.tpl', $module);
	}
}
