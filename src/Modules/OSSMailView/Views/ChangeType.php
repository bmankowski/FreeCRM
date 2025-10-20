<?php

namespace App\Modules\OSSMailView\Views;

/**
 * Change type action class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class ChangeType  extends \App\Modules\Vtiger\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$module = $request->getModule();
		$type_list = \App\Modules\OSSMailView\Models\Record::getMailType();
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $module);
		$viewer->assign('TYPE_LIST', $type_list);
		$viewer->view('ChangeType.tpl', $module);
	}
}
