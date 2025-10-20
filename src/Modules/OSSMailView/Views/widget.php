<?php

/**
 *
 * @package YetiForce.views
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
Class OSSMailView_widget_View extends \App\Modules\Vtiger\Views\Edit
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($request->getModule());
		if (!$permission) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}

		$srecord = $request->get('srecord');
		$smodule = $request->get('smodule');

		$recordPermission = \App\Modules\Users\Models\Privileges::isPermitted($smodule, 'DetailView', $srecord);
		if (!$recordPermission) {
			throw new \Exception\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$srecord = $request->get('srecord');
		$smodule = $request->get('smodule');
		$type = $request->get('type');
		$mode = $request->get('mode');
		$record = $request->get('record');
		$mailFilter = $request->get('mailFilter');
		$recordModel = \App\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);
		$config = \App\Modules\OSSMail\Models\Module::getComposeParameters();
		if ($request->has('limit')) {
			$config['widget_limit'] = $request->get('limit');
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('RECOLDLIST', $recordModel->$mode($srecord, $smodule, $config, $type, $mailFilter));
		$viewer->assign('MODULENAME', $moduleName);
		$viewer->assign('SMODULENAME', $smodule);
		$viewer->assign('RECORD', $record);
		$viewer->assign('SRECORD', $srecord);
		$viewer->assign('TYPE', $type);
		$viewer->assign('POPUP', $config['popup']);
		$viewer->assign('PRIVILEGESMODEL', \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel());
		$viewer->view('widgets.tpl', 'OSSMailView');
	}
}
