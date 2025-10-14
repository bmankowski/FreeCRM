<?php

namespace FreeCRM\Modules\Settings\LoginHistory\Views;
use FreeCRM\Modules\Settings\LoginHistoryModels\Record;



/**
 * 
 * @package YetiForce.Views
 * @license licenses/License.html
 * @author Mriusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class List extends \FreeCRM\Modules\Settings\Vtiger\Views\List
{

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		$viewer = $this->getViewer($request);
		$loginHistoryRecordModel = new \FreeCRM\Modules\Settings\LoginHistory\Models\Record();
		$usersList = $loginHistoryRecordModel->getAccessibleUsers();
		$viewer->assign('USERSLIST', $usersList);
		$viewer->assign('SELECTED_USER', $request->get('user_name'));
		parent::preProcess($request, false);
	}
}
