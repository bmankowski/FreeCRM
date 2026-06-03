<?php

namespace App\Modules\Calendar\Views;

/**
 *
 * @package YetiForce.views
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class ActivityStateModal  extends \App\Modules\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$id = $request->get('record');
		$recordInstance = \App\Modules\Base\Models\Record::getInstanceById($id, $moduleName);
		$permissionToSendEmail = \App\Modules\Mail\Models\Module::canUserSend((int) \App\User\CurrentUser::getId());
		
		// Pre-process record to add link_module_name if link exists
		$linkId = $recordInstance->get('link');
		if ($linkId) {
			$recordInstance->set('link_module_name', \App\Records\Record::getType($linkId));
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('PERMISSION_TO_SENDE_MAIL', $permissionToSendEmail);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('RECORD', $recordInstance);
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('SCRIPTS', $this->getScripts($request));
		$viewer->view('ActivityStateModal.tpl', $moduleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return array - List of \App\Modules\Base\Models\JsScript instances
	 */
	public function getScripts(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$jsFileNames = array(
			"modules.$moduleName.resources.ActivityStateModal",
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $jsScriptInstances;
	}
}
