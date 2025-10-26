<?php

namespace App\Modules\Settings\WebserviceUsers\Views;



/**
 * Edit View Class
 * @package YetiForce.Settings.Modal
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

class Edit extends \App\Modules\Settings\Base\Views\BasicModal
{

	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		parent::preProcess($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');
		$type = $request->get('typeApi');
		if (!empty($recordId)) {
			$recordModel = \App\Modules\Settings\WebserviceUsers\Models\Record::getInstanceById($recordId, $type);
		} else {
			$recordModel = \App\Modules\Settings\WebserviceUsers\Models\Record::getCleanInstance($type);
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('TYPE_API', $type);
		$viewer->assign('MODULE_MODEL', $recordModel->getModule());
		$viewer->view('Edit.tpl', $qualifiedModuleName);
		parent::postProcess($request);
	}
}
