<?php

namespace App\Modules\Settings\LoginHistory\Views;
use App\Modules\Settings\LoginHistoryModels\Record;



/**
 * 
 * @package YetiForce.Views
 * @license licenses/License.html
 * @author Mriusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class List extends \App\Modules\Settings\Vtiger\Views\List
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$viewer = $this->getViewer($request);
		$loginHistoryRecordModel = new \App\Modules\Settings\LoginHistory\Models\Record();
		$usersList = $loginHistoryRecordModel->getAccessibleUsers();
		$viewer->assign('USERSLIST', $usersList);
		$viewer->assign('SELECTED_USER', $request->get('user_name'));
		parent::preProcess($request, false);
	}
}
