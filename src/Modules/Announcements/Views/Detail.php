<?php

namespace FreeCRM\Modules\Announcements\Views;

/**
 * Announcements Detail View Class
 * @package YetiForce.View 
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use FreeCRM\Http\Vtiger_Request;
class Detail extends \Vtiger_Index_View
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('showUsers');
	}

	public function showUsers(\FreeCRM\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		$viewer = $this->getViewer($request);
		$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($moduleName);

		$users = [];
		foreach ($moduleModel->getUsers() as $userId => $name) {
			$row = $moduleModel->getMarkInfo($recordId, $userId);
			$row['name'] = $name;
			$users[$userId] = $row;
		}

		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('USERS', $users);
		$viewer->view('UsersList.tpl', $moduleName);
	}
}
