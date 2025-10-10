<?php

/**
 *
 * @package YetiForce.views
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
Class OSSMailView_widget_View extends Vtiger_Edit_View
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$userPrivilegesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($request->getModule());
		if (!$permission) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}

		$srecord = $request->get('srecord');
		$smodule = $request->get('smodule');

		$recordPermission = \FreeCRM\Modules\Users\Models\Privileges::isPermitted($smodule, 'DetailView', $srecord);
		if (!$recordPermission) {
			throw new \Exception\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
	}

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$srecord = $request->get('srecord');
		$smodule = $request->get('smodule');
		$type = $request->get('type');
		$mode = $request->get('mode');
		$record = $request->get('record');
		$mailFilter = $request->get('mailFilter');
		$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);
		$config = \FreeCRM\Modules\OSSMail\Models\Module::getComposeParameters();
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
		$viewer->assign('PRIVILEGESMODEL', \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel());
		$viewer->view('widgets.tpl', 'OSSMailView');
	}
}
